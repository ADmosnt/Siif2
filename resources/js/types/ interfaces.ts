export interface Cliente {
    value: string;
    label: string;
}

export interface Representante {
    id: string;
    nombre: string;
}

export interface Mayoristas {
    codigo: string
    mayorista: string

}

export interface Producto {
    id: number;
    codigo: string | number;
    producto: string;
    precio: number;
    lote: string;
    unidades: number;
}

export interface Actividad {
    idtipo_actividad: string;
    descripcionActividad: string;
}

export interface Evento {
    idtipo_incidentes: number;
    descripcionIncidente: string;
}

export interface CarritoItem {
    id:        string | number;
    codigo:    string | number;
    producto:  string
    unidades:  number
    unitPrice: number 
    precio:    number  
}

export interface MuestrasItem {
    codigo:      string
    producto:    string
    lote:        string
    unidades:    number  
}

export interface VisitaTemporal {
    id: number;
    idRFV: string;
    nombre_rfv: string;
    idCliente: string;
    nombre_cliente: string;
}