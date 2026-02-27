import { Ref } from 'vue';
import axios from 'axios';
import { Empresa } from '@/pages/Monitor.vue';
import vueDropzone from 'vue-dropzone';

//interfaz para los nombres de archivo permitidos
interface AllowedFilenames {
  personas?: {
    personas?: string;
    clientesRfv?: string;
  };
  productos?: {
    productos?: string;
    materiales?: string;
  };
}

// interfaz para los refs de Dropzone
interface DropzoneInstances {
  Dproducto?: Ref<typeof vueDropzone | null>;
  Dclimat?: Ref<typeof vueDropzone | null>;
  Dpersona?: Ref<typeof vueDropzone | null>;
  Dclirfv?: Ref<typeof vueDropzone | null>;
}

const config = {
  allowedFileTypes: ['xlsx'],
  maxFileSize: 5,
  maxFiles: 1,
  allowedFilenames: {
    personas: {
      personas: 'RFV_Cliente_Mayorista.xlsx',
      clientesRfv: 'Cliente_RFV.xlsx',
    },
    productos: {
      productos: 'Productos.xlsx',
      materiales: 'Materiales_Clientes.xlsx'
    }
  } as AllowedFilenames
};

// Modifica la firma del composable para aceptar las instancias de dropzone
export const useDropzone = (
  empresa: Ref<Empresa | null>,
  dropzones: DropzoneInstances
) => {

  const validateFile = (file: File, tipoArchivo: string, currentTab: keyof AllowedFilenames): { isValid: boolean; error: string | null } => {
    const fileSizeInMB = file.size / (1024 * 1024);
    const fileExt = file.name.split('.').pop()?.toLowerCase();
    const expectedFilename = config.allowedFilenames[currentTab]?.[tipoArchivo as keyof NonNullable<AllowedFilenames[typeof currentTab]>];

    // Validar tipo de archivo
    if (!fileExt || !config.allowedFileTypes.includes(fileExt)) {
      return { isValid: false, error: `Tipo no permitido. Solo ${config.allowedFileTypes.join(', ')}` };
    }

    // Validar tamaño
    if (fileSizeInMB > config.maxFileSize) {
      return { isValid: false, error: `Excede el tamaño máximo de ${config.maxFileSize}MB` };
    }

    // Validar nombre del archivo (si hay un nombre esperado)
    if (expectedFilename && file.name !== expectedFilename) {
      return { isValid: false, error: `El archivo debe llamarse: ${expectedFilename}` };
    }

    return { isValid: true, error: null };
  };

  const uploadFile = async (file: File, endpoint: string) => {
    if (!empresa.value) return;
    const formData = new FormData();
    formData.append('archivo_excel', file);
    formData.append('idFabricante', empresa.value.idFabricante);
    try {
      await axios.post(endpoint, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      return { success: true, error: null };
    } catch (error) {
      console.error('Error al subir el archivo:', error);
      return { success: false, error: 'Hubo un error al procesar el archivo' };
    }
  };

  //habilitar/deshabilitar Dropzones
  function disableAllDropzones() {
    for (const key in dropzones) {
      const dropzoneRef = dropzones[key as keyof DropzoneInstances];
      dropzoneRef?.value?.disable();
    }
  }

  function enableAllDropzones() {
    for (const key in dropzones) {
      const dropzoneRef = dropzones[key as keyof DropzoneInstances];
      dropzoneRef?.value?.enable();
    }
  }

  return {
    validateFile,
    uploadFile,
    disableAllDropzones,
    enableAllDropzones,
  };
};