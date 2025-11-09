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
            key: string,               // Field group key (e.g., 'location_fields_group')
            title: string,             // Group title (e.g., 'Location Information')
            fields: Array<{
                key: string,           // Unique field key
                name: string,          // Field name (e.g., 'phone')
                label: string,         // Visible label (e.g., 'Phone Number')
                type: string,          // Field type (text, textarea, image, etc.)
                has_value: boolean,    // Whether field has value in source post
                will_overwrite: boolean // Whether it will overwrite existing value
            }>
        }>,
        source_post: {
            id: number,
            title: string,
            stats: { /* stats structure */ }
        },
        target_post: {
            id: number,
            title: string,
            stats: { /* stats structure */ }
        },
        message?: string
    }
}
```

### 3. `acf_clone_execute_clone`

**Purpose**: Execute cloning of selected fields  
**Trigger**: When confirming the clone operation (Step 3)

**Request Data**:
```javascript
{
    action: 'acf_clone_execute_clone',
    nonce: string,
    target_post_id: number,    // Target post ID
    source_post_id: number,    // Source post ID
    field_keys: Array<string>, // Array of field names to clone
    options: {
        create_backup: boolean,      // Whether to create backup before cloning
        preserve_empty: boolean,     // Whether to preserve empty values
        overwrite_existing: boolean  // Whether to overwrite existing values (default: true)
    }
}
```

**Response Structure**:
```javascript
{
    success: boolean,
    data: {
        cloned_count: number,              // Number of successfully cloned fields
        skipped_count: number,             // Number of skipped fields
        cloned_fields: Array<string>,      // Names of cloned fields
        skipped_fields: Array<string>,     // Names of skipped fields
        source_post: {
            id: number,
            title: string
        },
        target_post: {
            id: number,
            title: string
        },
        backup_info?: {                    // Only if create_backup = true
            backup_id: string,             // Backup identifier
            created_at: string             // Creation timestamp
        },
        operation_summary: {
            total_requested: number,       // Total requested fields
            successful: number,            // Successful operations
            failed: number                 // Failed operations
        },
        message?: string                   // Descriptive result message
    }
}
```

## Error Handling

All endpoints can return errors in the following format:

```javascript
{
    success: false,
    data: {
        message: string,              // Descriptive error message
        error_code?: string,          // Optional error code
        details?: Object              // Additional error details
    }
}
```

## Common AJAX Errors

If the AJAX request fails completely (network issues, server down, etc.), jQuery will call the error callback with:

```javascript
// onAjaxError callback parameters
xhr: {
    status: number,               // HTTP status code (404, 500, etc.)
    statusText: string,           // HTTP status text
    responseText: string,         // Raw server response
    responseJSON?: Object         // Parsed response if valid JSON
},
status: string,                   // 'error', 'timeout', 'abort', etc.
error: string                     // Error message
```

## Development Notes

1. **Data Validation**: Always check `response.success` before accessing `response.data`
2. **Nested Structure**: Fields are organized in groups, access as `response.data.fields[i].fields[j]`
3. **Field States**: Use `has_value` and `will_overwrite` to show visual indicators
4. **Array Handling**: `response.data.fields` is an array, not an object with keys
5. **Debugging**: All console.log statements include the complete response structure for debugging

## Usage Example

```javascript
// Load fields from source post
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
        
        // Iterate over field groups
        response.data.fields.forEach(group => {
            console.log('Group:', group.title);
            
            // Iterate over individual fields
            group.fields.forEach(field => {
                console.log(`- ${field.label} (${field.type})`);
                if (field.will_overwrite) {
                    console.warn('  ⚠️ Will overwrite existing value');
                }
            });
        });
    }
});
```

## JavaScript Testing

### QUnit Test Suite (Future Implementation)

For comprehensive JavaScript testing coverage, the plugin can integrate QUnit tests following WordPress core standards.

**Recommended Setup**:

1. **Test Directory Structure**:
   ```
   tests/
   ├── qunit/
   │   ├── index.html           # QUnit test runner
   │   ├── tests/
   │   │   ├── admin.test.js    # Tests for admin.js
   │   │   └── utils.test.js    # Tests for utility functions
   │   └── fixtures/
   │       └── ajax-responses.js # Mock AJAX responses
   ```

2. **Test Coverage Goals**:
   - Modal state management and transitions
   - AJAX request/response handling
   - Field selection and validation logic
   - Error handling and user feedback
   - Backup option processing

3. **Running QUnit Tests**:
   ```bash
   # Via browser (recommended for development)
   open tests/qunit/index.html
   
   # Or navigate to:
   http://localhost/wp-content/plugins/silver-assist-acf-clone-fields/tests/qunit/index.html
   ```

4. **Key Testing Areas**:
   - **Modal Functionality**: Step transitions, data persistence
   - **AJAX Handlers**: Request formatting, response parsing, error handling
   - **Field Selection**: Checkbox logic, group selection, conflict detection
   - **Data Validation**: Field key validation, option processing
   - **UI State**: Loading indicators, error messages, success feedback

**Reference**: [WordPress QUnit Testing Handbook](https://make.wordpress.org/core/handbook/testing/automated-testing/qunit/)

**Note**: QUnit implementation is planned for future releases to achieve comprehensive JavaScript test coverage alongside the existing PHPUnit test suite.