<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as MaatValidationException;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class ExcelImportService
{
    public function processImport(object $importObject, UploadedFile $file, string $importContextName = 'datos'): JsonResponse
    {
        try {
            Excel::import($importObject, $file);

            $results = $this->collectImportResults($importObject);

            $summary = $this->getImportSummary(
                $results['validationFailures'],
                $results['processingErrors'],
                $importContextName
            );

            if ($results['hasValidationFailures'] || $results['hasProcessingErrors']) {
                return $this->buildPartialSuccessResponse($results, $summary, $importContextName);
            }

            return response()->json(['message' => ucfirst($importContextName) . ' cargados y procesados correctamente.'], 200);

        } catch (MaatValidationException $e) {
            return $this->handleValidationException($e, $importContextName);
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            return $this->handlePhpSpreadsheetException($e, $importContextName);
        } catch (QueryException $e) {
            return $this->handleQueryException($e, $importContextName);
        } catch (\Exception $e) {
            return $this->handleGenericException($e, $importContextName);
        }
    }

    /**
     * Extrae fallos de validación y errores de procesamiento del objeto import.
     * @return array
     */
    private function collectImportResults(object $importObject): array
    {
        $validationFailures = [];
        $processingErrors = [];
        $hasValidationFailures = false;
        $hasProcessingErrors = false;

        if (method_exists($importObject, 'getFailures')) {
            $validationFailures = $importObject->getFailures() ?: [];
            $hasValidationFailures = !empty($validationFailures);
        }

        if (method_exists($importObject, 'getErrors')) {
            $processingErrors = $importObject->getErrors() ?: [];
            $hasProcessingErrors = !empty($processingErrors);
        }

        return [
            'validationFailures' => $validationFailures,
            'processingErrors' => $processingErrors,
            'hasValidationFailures' => $hasValidationFailures,
            'hasProcessingErrors' => $hasProcessingErrors,
        ];
    }

    private function buildPartialSuccessResponse(array $results, array $summary, string $importContextName): JsonResponse
    {
        $responsePayload = [
            'message' => ucfirst($importContextName) . ' procesados con algunos problemas.',
            'summary' => $summary,
        ];

        if ($results['hasValidationFailures']) {
            $responsePayload['validation_failures'] = $this->formatValidationFailures($results['validationFailures']);
            Log::warning("Fallos de validación recolectados al importar {$importContextName}: ", $responsePayload['validation_failures']);
        }

        if ($results['hasProcessingErrors']) {
            $responsePayload['processing_errors'] = $this->formatCollectedErrors($results['processingErrors']);
            Log::error("Errores de procesamiento recolectados (SkipsOnError) al importar {$importContextName}: ", $responsePayload['processing_errors']);
        }

        return response()->json($responsePayload, 207);
    }

    private function handleValidationException(MaatValidationException $e, string $importContextName): JsonResponse
    {
        $formattedErrors = $this->formatValidationFailures($e->failures());
        $summary = $this->getImportSummary($e->failures(), [], $importContextName, true);

        Log::warning("Errores de validación (ValidationException) al importar {$importContextName}: ", $formattedErrors);
        return response()->json([
            'message' => "Se encontraron errores de validación críticos en el archivo de {$importContextName}. La importación no pudo completarse.",
            'summary' => $summary,
            'validation_errors' => $formattedErrors,
        ], 422);
    }

    private function handlePhpSpreadsheetException(\PhpOffice\PhpSpreadsheet\Exception $e, string $importContextName): JsonResponse
    {
        $userMessage = "Error con el archivo Excel de {$importContextName}: Problema al leer el archivo.";
        if (str_contains($e->getMessage(), 'El nombre de la hoja solicitada ') && str_contains($e->getMessage(), 'no se encuentra')) {
            $userMessage = "Error: La hoja con el nombre esperado no se encontró en el archivo de {$importContextName}. Verifica el nombre de la hoja.";
        }
        Log::error("Error de PhpSpreadsheet al importar {$importContextName}: " . $e->getMessage());
        return response()->json([
            'message' => $userMessage,
            'details' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor procesando el archivo.',
        ], 400);
    }

    private function handleQueryException(QueryException $e, string $importContextName): JsonResponse
    {
        $errorCode = $e->errorInfo[1] ?? null;
        $userMessage = "Error de base de datos al procesar lotes de {$importContextName}.";
        if ($errorCode == 1062) {
            $userMessage = "Error de base de datos: Se detectó un valor duplicado general al procesar lotes de {$importContextName}.";
        }
        Log::error("Error de QueryException (nivel batch) al importar {$importContextName}: " . $e->getMessage());
        return response()->json([
            'message' => $userMessage,
            'details' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor al guardar datos.',
        ], 409);
    }

    private function handleGenericException(\Exception $e, string $importContextName): JsonResponse
    {
        Log::error("Error general (no skip) al importar {$importContextName}: " . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
        $userMessage = "Ocurrió un error inesperado durante la importación de {$importContextName}.";
        if (is_a($e, 'App\\Exceptions\\CustomUserFacingException') || (str_starts_with($e->getMessage(), "La hoja '") && str_ends_with($e->getMessage(), "' no existe en el archivo."))) {
            $userMessage = $e->getMessage();
        } elseif (config('app.debug')) {
            $userMessage = $e->getMessage();
        }
        return response()->json([
            'message' => $userMessage,
            'details' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor inesperado.',
        ], 500);
    }

    /**
     * Formatea las fallas de validación (de SkipsOnFailure o ValidationException).
     * @param Failure[] $failures
     * @return array
     */
    private function formatValidationFailures(array $failures): array
    {
        $errorMessages = [];
        foreach ($failures as $failure) {
            $errorMessages[] = [
                'fila' => $failure->row(),
                'columna_excel' => $failure->attribute(),
                'errores' => $failure->errors(),
                'valores_problematicos' => $failure->values(),
            ];
        }
        return $errorMessages;
    }

    /**
     * Formatea los errores recolectados por SkipsOnError.
     * @param Throwable[] $errors
     * @return array
     */
    private function formatCollectedErrors(array $errors): array
    {
        $errorMessages = [];
        foreach ($errors as $error) {
            $message = "";
            $type = get_class($error);
            $rowInfo = "";
            if (method_exists($error, 'row')) {
                try {
                    $rowVal = call_user_func([$error, 'row']);
                    $rowInfo = " (Fila aprox.: " . $rowVal . ")";
                } catch (\Throwable $t) {
                    // ignore - some error objects may expose row() but still fail
                }
            } elseif (preg_match('/ en la fila (\d+)/', $error->getMessage(), $matches)) {
                $rowInfo = " (Fila aprox.: " . $matches[1] . ")";
            }

            if ($error instanceof QueryException) {
                $errorCode = $error->errorInfo[1] ?? 'desconocido';
                $message = "Error de base de datos{$rowInfo} (Código: {$errorCode}). ";
                if ($errorCode == 1062) {
                    $message = "Entrada duplicada en base de datos para una fila{$rowInfo}.";
                } else {
                    $message .= $error->getMessage();
                }
            } else {
                $message .= $error->getMessage() . $rowInfo;
            }

            $errorMessages[] = [
                'tipo_error' => $type,
                'mensaje_error' => $message,
            ];
        }
        return $errorMessages;
    }

    private function getImportSummary(array $validationFailures, array $processingErrors, string $importContextName, bool $isCriticalError = false): array
    {
        $summary = [
            'total_filas_fallidas' => count($validationFailures) + count($processingErrors),
            'detalles_por_tipo' => [],
            'mensaje_general' => '',
            'estado_general' => $isCriticalError ? 'CRITICAL_ERROR' : 'PARTIAL_SUCCESS'
        ];

        // Procesar fallos de validación
        $validationErrorCounts = [];
        foreach ($validationFailures as $failure) {
            foreach ($failure->errors() as $errorDetail) {
                if (str_contains($errorDetail, 'obligatorio') || str_contains($errorDetail, 'required')) {
                    $key = 'campos_vacios_o_requeridos';
                } elseif (str_contains($errorDetail, 'existe') || str_contains($errorDetail, 'exists')) {
                    $key = 'id_no_existente_en_catálogo';
                } elseif (str_contains($errorDetail, 'formato') || str_contains($errorDetail, 'date_format') || str_contains($errorDetail, 'numerico')) {
                    $key = 'formato_de_datos_incorrecto';
                } elseif (str_contains($errorDetail, 'max')) {
                    $key = 'longitud_maxima_excedida';
                } else {
                    $key = 'otros_errores_de_validación';
                }
                $validationErrorCounts[$key] = ($validationErrorCounts[$key] ?? 0) + 1;
            }
        }
        foreach ($validationErrorCounts as $type => $count) {
            $summary['detalles_por_tipo'][] = ['tipo' => ucfirst(str_replace('_', ' ', $type)), 'cantidad' => $count];
        }

        // Procesar errores de procesamiento (SkipsOnError)
        $processingErrorCounts = [];
        foreach ($processingErrors as $error) {
            if ($error instanceof QueryException) {
                $errorCode = $error->errorInfo[1] ?? null;
                if ($errorCode == 1062) {
                    $key = 'entradas_duplicadas_en_bd';
                } else {
                    $key = 'errores_de_base_de_datos';
                }
            } elseif ($error instanceof \PhpOffice\PhpSpreadsheet\Exception) {
                $key = 'errores_de_lectura_de_excel';
            } else {
                $key = 'errores_inesperados_al_procesar';
            }
            $processingErrorCounts[$key] = ($processingErrorCounts[$key] ?? 0) + 1;
        }
        foreach ($processingErrorCounts as $type => $count) {
            $summary['detalles_por_tipo'][] = ['tipo' => ucfirst(str_replace('_', ' ', $type)), 'cantidad' => $count];
        }

        // Determinar mensaje general
        if ($isCriticalError) {
            $summary['mensaje_general'] = "La importación no pudo completarse debido a errores críticos.";
        } elseif ($summary['total_filas_fallidas'] > 0) {
            $summary['mensaje_general'] = "La importación finalizó con " . $summary['total_filas_fallidas'] . " filas con problemas.";
        } else {
            $summary['mensaje_general'] = "La importación se completó sin problemas.";
        }

        return $summary;
    }
}