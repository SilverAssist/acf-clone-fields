# ACF Clone Fields - AJAX API Reference

## Overview

This document describes the exact data structures exchanged between the frontend JavaScript and backend PHP through AJAX. Use it as a quick reference during development.

## AJAX Endpoints


### 1. `acf_clone_get_source_posts`

**Purpose**: Get list of available posts as cloning source  
**Trigger**: When opening the modal (Step 1)

**Request Data**:
```javascript
{
    action: 'acf_clone_get_source_posts',
    nonce: string,
    post_id: number,
    post_type: string
}
```

**Response Structure**:
```javascript
{
    success: boolean,
    data: {
        posts: Array<{
            id: number,                    // Source post ID
            title: string,                 // Post title
            stats: {
                total_fields: number,      // Total fields in the post
                cloneable_fields: number,  // Fields that can be cloned
                fields_with_values: number,// Fields that have values
                group_fields: number,      // Group type fields
                repeater_fields: number,   // Repeater type fields
                total_groups: number       // Total field groups
            }
        }>,
        target_post: {
            id: number,                    // Target post ID (current)
            title: string,                 // Target post title
            stats: { /* same structure as above */ }
        },
        message?: string                   // Error message if success = false
    }
}
```

### 2. `acf_clone_get_source_fields`

**Purpose**: Get specific fields from the selected source post  
**Trigger**: When selecting a source post (Step 2)

**Request Data**:
```javascript
{
    action: 'acf_clone_get_source_fields',
    nonce: string,
    target_post_id: number,    // Target post ID
    source_post_id: number     // Selected source post ID
}
```

**Response Structure**:
```javascript
{
    success: boolean,
    data: {
        fields: Array<{
            key: string,               // Clave del grupo de campos (ej: 'location_fields_group')
            title: string,             // Título del grupo (ej: 'Location Information')
            fields: Array<{
                key: string,           // Clave única del campo
                name: string,          // Nombre del campo (ej: 'phone')
                label: string,         // Etiqueta visible (ej: 'Phone Number')
                type: string,          // Tipo de campo (text, textarea, image, etc.)
                has_value: boolean,    // Si el campo tiene valor en el post fuente
                will_overwrite: boolean // Si va a sobrescribir un valor existente
            }>
        }>,
        source_post: {
            id: number,
            title: string,
            stats: { /* estructura de stats */ }
        },
        target_post: {
            id: number,
            title: string,
            stats: { /* estructura de stats */ }
        },
        message?: string
    }
}
```

### 3. `acf_clone_execute_clone`
**Propósito**: Ejecutar el clonado de campos seleccionados  
**Trigger**: Al confirmar el clonado (Paso 3)

**Request Data**:
```javascript
{
    action: 'acf_clone_execute_clone',
    nonce: string,
    target_post_id: number,    // ID del post destino
    source_post_id: number,    // ID del post fuente
    field_keys: Array<string>, // Array de nombres de campos a clonar
    options: {
        create_backup: boolean,      // Si crear respaldo antes de clonar
        preserve_empty: boolean,     // Si preservar valores vacíos
        overwrite_existing: boolean  // Si sobrescribir valores existentes (true por defecto)
    }
}
```

**Response Structure**:
```javascript
{
    success: boolean,
    data: {
        cloned_count: number,              // Número de campos clonados exitosamente
        skipped_count: number,             // Número de campos omitidos
        cloned_fields: Array<string>,      // Nombres de campos clonados
        skipped_fields: Array<string>,     // Nombres de campos omitidos
        source_post: {
            id: number,
            title: string
        },
        target_post: {
            id: number,
            title: string
        },
        backup_info?: {                    // Solo si create_backup = true
            backup_id: string,             // Identificador del respaldo
            created_at: string             // Timestamp de creación
        },
        operation_summary: {
            total_requested: number,       // Total de campos solicitados
            successful: number,            // Operaciones exitosas
            failed: number                 // Operaciones fallidas
        },
        message?: string                   // Mensaje descriptivo del resultado
    }
}
```

## Manejo de Errores

Todos los endpoints pueden devolver errores en el siguiente formato:

```javascript
{
    success: false,
    data: {
        message: string,              // Mensaje de error descriptivo
        error_code?: string,          // Código de error opcional
        details?: Object              // Detalles adicionales del error
    }
}
```

## Errores AJAX Comunes

Si el request AJAX falla completamente (problemas de red, servidor caído, etc.), jQuery llamará el callback de error con:

```javascript
// Parámetros del callback onAjaxError
xhr: {
    status: number,               // Código HTTP (404, 500, etc.)
    statusText: string,           // Texto del estado HTTP
    responseText: string,         // Respuesta cruda del servidor
    responseJSON?: Object         // Respuesta parseada si es JSON válido
},
status: string,                   // 'error', 'timeout', 'abort', etc.
error: string                     // Mensaje de error
```

## Notas de Desarrollo

1. **Validación de Datos**: Siempre verifica `response.success` antes de acceder a `response.data`
2. **Estructura Anidada**: Los campos están organizados en grupos, accede como `response.data.fields[i].fields[j]`
3. **Estados de Campo**: Usa `has_value` y `will_overwrite` para mostrar indicadores visuales
4. **Manejo de Arrays**: `response.data.fields` es un array, no un objeto con claves
5. **Debugging**: Todos los console.log incluyen la estructura completa de response para debugging

## Ejemplo de Uso

```javascript
// Cargar campos del post fuente
$.ajax({
    url: ajaxurl,
    data: {
        action: 'acf_clone_get_source_fields',
        target_post_id: 123,
        source_post_id: 456
    },
    success: function(response) {
        if (!response.success) {
            console.error('Error:', response.data.message);
            return;
        }
        
        // Iterar sobre grupos de campos
        response.data.fields.forEach(group => {
            console.log('Grupo:', group.title);
            
            // Iterar sobre campos individuales
            group.fields.forEach(field => {
                console.log(`- ${field.label} (${field.type})`);
                if (field.will_overwrite) {
                    console.warn('  ⚠️ Sobrescribirá valor existente');
                }
            });
        });
    }
});
```