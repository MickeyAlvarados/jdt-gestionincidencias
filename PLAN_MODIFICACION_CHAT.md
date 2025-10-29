# Plan de Modificación: Sistema de Chat con IA - Base de Conocimientos

## Estado Actual: Sistema con Búsqueda por Palabras Clave y Almacenamiento de Conversaciones

### Última actualización: 21 de Octubre 2025

---

## 1. Descripción General

El sistema de gestión de incidencias JDT cuenta con un chat inteligente que:
- Busca soluciones en una base de conocimientos
- Usa DeepSeek AI para resolver problemas técnicos
- Almacena conversaciones completas en formato JSON
- Aprende de casos previos exitosos

---

## 2. Arquitectura Actual

### 2.1 Base de Datos - Tabla `bd_conocimientos`

```sql
CREATE TABLE bd_conocimientos (
    id BIGINT PRIMARY KEY,
    id_incidencia BIGINT,
    descripcion_problema TEXT,
    fecha_incidencia DATE,
    comentario_resolucion JSON,  -- Conversación completa en formato JSON
    empleado_resolutor VARCHAR(100)
);
```

**Estructura del campo JSON `comentario_resolucion`:**

```json
[
    {
        "rol": "usuario",
        "contenido": "Mi impresora no funciona"
    },
    {
        "rol": "ia",
        "contenido": "Vamos a revisar la impresora. ¿Qué tipo de impresora tienes?"
    },
    {
        "rol": "usuario",
        "contenido": "Es una HP LaserJet"
    },
    {
        "rol": "ia",
        "contenido": "Perfecto. Sigue estos pasos: 1. Verifica que esté encendida..."
    }
]
```

### 2.2 Flujo del Sistema

```
Usuario envía mensaje
    ↓
ProcessChatMessage (Job en cola)
    ↓
AgenteIAService::procesarProblema()
    ↓
┌─────────────────────────────────────────┐
│ consultarBaseConocimientos()            │
│ → BdConocimiento::buscarSolucionesSimi… │
│                                         │
│ Búsqueda por Palabras Clave:           │
│   1. Extraer palabras clave (>3 chars) │
│   2. Filtrar stop words                │
│   3. ILIKE "%palabra%" en problema      │
│   4. Retornar top 3 resultados          │
└─────────────────────────────────────────┘
    ↓
┌─────────────────────────────────────────┐
│ ¿Se encontraron casos similares?        │
└─────────────────────────────────────────┘
    ↓                    ↓
   SÍ                   NO
    ↓                    ↓
DeepSeek con          DeepSeek
conversaciones        sin contexto
previas como
contexto
    ↓
Respuesta IA
    ↓
Broadcast vía WebSocket
    ↓
Frontend actualiza chat en tiempo real
```

### 2.3 Modelos y Servicios

**BdConocimiento.php:**
- `buscarSolucionesSimilares()`: Búsqueda por palabras clave usando ILIKE
- `extraerPalabrasClave()`: Filtra stop words y palabras cortas
- Casting automático de JSON: `'comentario_resolucion' => 'array'`

**AgenteIAService.php:**
- `procesarProblema()`: Lógica principal de resolución
- `consultarBaseConocimientos()`: Búsqueda de casos similares (top 3)
- `detectarCategoria()`: Hardware, Red, Impresora, Software, etc.

**DeepSeekService.php:**
- `resolverProblema()`: Integración con API de DeepSeek
- `construirPromptSoporte()`: Incluye conversaciones previas en el prompt
- `evaluarCapacidadResolucion()`: Detecta si requiere escalamiento

---

## 3. Ventajas del Sistema Actual

✅ **Simplicidad**: No requiere dependencias externas (pgvector, Python)
✅ **Compatibilidad**: Funciona en cualquier PostgreSQL estándar
✅ **Almacenamiento rico**: Guarda toda la conversación, no solo la solución final
✅ **Contexto completo**: La IA puede aprender del flujo conversacional
✅ **Fácil mantenimiento**: Estructura JSON flexible

---

## 4. Limitaciones Conocidas

⚠️ **Búsqueda básica**: Solo coincidencias textuales, no entiende sinónimos
⚠️ **Sin ranking semántico**: No hay score de similitud real
⚠️ **Sensible a redacción**: "PC" vs "computadora" no coinciden automáticamente

---

## 5. Formato de Conversaciones Almacenadas

Cuando un problema se resuelve exitosamente, se almacena:

```php
// En Incidencia.php::guardarEnConocimientos()
BdConocimiento::create([
    'id' => $nextId,
    'id_incidencia' => $this->id,
    'descripcion_problema' => $this->descripcion_problema,
    'fecha_incidencia' => $this->fecha_incidencia,
    'comentario_resolucion' => [
        ['rol' => 'usuario', 'contenido' => 'Mensaje 1'],
        ['rol' => 'ia', 'contenido' => 'Respuesta 1'],
        ['rol' => 'usuario', 'contenido' => 'Mensaje 2'],
        ['rol' => 'ia', 'contenido' => 'Solución final']
    ],
    'empleado_resolutor' => 'Agente IA'
]);
```

---

## 6. Uso de Conversaciones Previas por DeepSeek

Cuando se encuentran casos similares, el prompt incluye:

```
Problema reportado: [Problema actual del usuario]

=== CONVERSACIONES PREVIAS CON PROBLEMAS SIMILARES ===

Caso #1:
Problema: La impresora no imprime
Conversación que llevó a la solución:
  - usuario: Mi impresora no funciona
  - ia: ¿Qué tipo de impresora es?
  - usuario: HP LaserJet
  - ia: 1. Verifica el cable USB...
Resuelto por: Agente IA
Fecha: 2025-10-15

Caso #2:
Problema: Error al imprimir
...

=== FIN DE CONVERSACIONES PREVIAS ===

INSTRUCCIÓN: Revisa las conversaciones anteriores de casos similares.
Aprende de cómo se resolvieron esos problemas y adapta la solución al
contexto actual del usuario. Genera una respuesta clara, paso a paso y
personalizada.
```

---

## 7. Checklist de Verificación del Sistema

### Base de Datos
- [ ] Tabla `bd_conocimientos` tiene campo `comentario_resolucion` tipo JSON
- [ ] El modelo tiene casting: `'comentario_resolucion' => 'array'`

### Servicios
- [ ] `BdConocimiento::buscarSolucionesSimilares()` retorna array con 'conversacion'
- [ ] `AgenteIAService::consultarBaseConocimientos()` funciona correctamente
- [ ] `DeepSeekService::construirPromptSoporte()` incluye conversaciones previas

### Flujo Completo
- [ ] Chat envía mensaje → Job procesa
- [ ] Se buscan conversaciones similares en BD
- [ ] DeepSeek recibe contexto de conversaciones
- [ ] Respuesta se transmite vía WebSocket
- [ ] Conversaciones exitosas se guardan en BD

---

## 8. Comandos Útiles

```bash
# Ver estructura de la tabla
php artisan tinker
>>> DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'bd_conocimientos'");

# Probar búsqueda
>>> App\Models\BdConocimiento::buscarSolucionesSimilares('problema impresora');

# Ver conversaciones almacenadas
>>> App\Models\BdConocimiento::whereNotNull('comentario_resolucion')->first()->comentario_resolucion;

# Verificar migraciones
php artisan migrate:status
```

---

## 9. Mejoras Futuras Propuestas

### 9.1 Mejorar Búsqueda (Sin Vectores)
- Implementar búsqueda Full-Text de PostgreSQL (`to_tsvector`)
- Agregar ranking por relevancia (ts_rank)
- Usar diccionario español para stemming

```sql
-- Ejemplo de búsqueda mejorada con Full-Text Search
SELECT *, ts_rank(to_tsvector('spanish', descripcion_problema), query) as ranking
FROM bd_conocimientos, plainto_tsquery('spanish', 'impresora problema') query
WHERE to_tsvector('spanish', descripcion_problema) @@ query
ORDER BY ranking DESC
LIMIT 3;
```

### 9.2 Análisis de Conversaciones
- Identificar patrones en conversaciones exitosas
- Detectar preguntas de aclaración más efectivas
- Optimizar flujos de resolución

### 9.3 Métricas de Calidad
- Tiempo promedio hasta la resolución
- Cantidad de mensajes por problema resuelto
- Categorías con más éxito de IA vs escalamiento

---

## 10. Historial de Cambios

### 21 de Octubre 2025
- ❌ Eliminada implementación de búsqueda vectorial con pgvector
- ❌ Eliminado EmbeddingService.php
- ❌ Eliminado comando GenerarEmbeddings
- ❌ Eliminada migración add_vector_search_to_bd_conocimientos
- ✅ Modificada tabla bd_conocimientos: `comentario_resolucion` ahora es JSON
- ✅ Sistema simplificado a búsqueda por palabras clave
- ✅ Almacenamiento de conversaciones completas en formato JSON
- ✅ DeepSeek ahora recibe contexto de conversaciones previas

### Razón del cambio
Se optó por simplificar el sistema eliminando la complejidad de embeddings vectoriales
y en su lugar almacenar las conversaciones completas. Esto permite:
- Menor complejidad técnica
- No dependencias externas (pgvector, Python)
- Contexto más rico (toda la conversación vs solo texto)
- Más fácil de mantener y depurar
- La IA aprende del flujo conversacional, no solo de la solución final

---

## 11. Soporte y Documentación

- Documentación principal: `README.md`
- Guía WebSocket: `WEBSOCKETS_GUIDE.md`
- Configuración IA: `CHAT_IA_SETUP.md`
- Instrucciones Claude: `CLAUDE.md`

---

**Mantenedor:** Equipo JDT
**Última revisión:** 21/10/2025
