import axios from 'axios';
import { Ref } from 'vue';
import { Empresa } from '@/pages/Monitor.vue';

export const useExport = (empresa: Ref<Empresa | null>) => {
  const descargarArchivo = async (url: string, nombreArchivoBase: string) => {
    if (empresa.value && empresa.value.idFabricante) {
      try {
        const response = await axios.post(
          url,
          { idFabricante: empresa.value.idFabricante },
          { responseType: 'blob' }
        );

        const urlBlob = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = urlBlob;
        link.setAttribute('download', `${nombreArchivoBase}.xlsx`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(urlBlob);

        console.log(`Archivo ${nombreArchivoBase} descargado`);
        return { success: true, error: null };
      } catch (error: any) {
        console.error(`Error al descargar ${nombreArchivoBase}:`, error);
        return { success: false, error: 'Hubo un error al descargar el archivo' };
      }
    } else {
      console.warn('No se puede descargar: la información de la empresa o el ID del fabricante no están disponibles.');
      return { success: false, error: 'No se ha seleccionado una empresa' };
    }
  };

  return { descargarArchivo };
};