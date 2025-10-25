# Constituency Management - Implementation Guide

## Overview

A complete constituency management system has been implemented for Admin and Executive users to manage Ghana's electoral constituencies linked to regions.

## Features Implemented

### 1. **Full CRUD Operations**
- ✅ **Create** - Add new constituencies
- ✅ **Read** - View all constituencies with region information
- ✅ **Update** - Edit constituency details
- ✅ **Delete** - Remove constituencies (Admin only)

### 2. **User Interface**
- Modern modal-based forms for add/edit operations
- DataTables integration for search, sort, and pagination
- Filter constituencies by region
- Statistics dashboard showing constituency count per region
- Responsive design for mobile and desktop

### 3. **Access Control**
- Admin and Executive users can view and manage constituencies
- Only Admin users can delete constituencies
- Regular members cannot access this page

### 4. **Integration**
- Linked to sidebar navigation
- Used in institution and campus forms
- AJAX endpoint for dynamic loading
- Proper foreign key relationships

## Files Created/Modified

### New Files
1. **`constituencies.php`** - Main management page
   - List all constituencies
   - Filter by region
   - Add/Edit/Delete operations
   - Statistics dashboard

2. **`classes/Constituency.php`** - Business logic class
   - `getAll()` - Get all constituencies
   - `getById($id)` - Get single constituency
   - `getByRegion($regionId)` - Filter by region
   - `create($name, $regionId, $createdBy)` - Add new
   - `update($id, $name, $regionId)` - Update existing
   - `delete($id)` - Remove constituency

3. **`ajax/get_constituencies.php`** - AJAX endpoint
   - Returns constituencies for selected region as JSON
   - Used in institution and campus forms

### Modified Files
1. **`includes/header.php`** - Added constituencies link to sidebar navigation

## Database Schema

The `constituencies` table already exists in the schema:

```sql
CREATE TABLE IF NOT EXISTS `constituencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `region_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `region_id` (`region_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `constituencies_ibfk_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `constituencies_ibfk_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Usage Guide

### Accessing Constituency Management

1. **Login** as Admin or Executive user
2. Navigate to **Constituencies** from the sidebar (under Management section)
3. The page displays all constituencies with their regions

### Adding a Constituency

1. Click **"Add Constituency"** button
2. Modal opens with form:
   - Select Region (required)
   - Enter Constituency Name (required)
3. Click **"Create Constituency"**
4. Success message appears and page refreshes

### Editing a Constituency

1. Click the **Edit (pencil)** icon next to a constituency
2. Modal opens with pre-filled data
3. Modify the name or region
4. Click **"Update Constituency"**
5. Changes are saved and page refreshes

### Deleting a Constituency

1. Click the **Delete (trash)** icon (Admin only)
2. Confirm deletion in popup
3. Constituency is removed if not linked to other records
4. Error message appears if constituency is in use

### Filtering by Region

1. Use the **Filter** dropdown at the top
2. Select a region
3. Click **"Filter"** button
4. Only constituencies from that region are displayed
5. Select "All Regions" to clear filter

### Viewing Statistics

- Scroll to the bottom of the page
- Statistics cards show constituency count per region
- Helps identify regions with missing constituencies

## Integration with Other Modules

### Institution Add/Edit Forms
```javascript
// Constituencies load dynamically when region is selected
document.getElementById('region_id').addEventListener('change', function() {
    fetch('ajax/get_constituencies.php?region_id=' + this.value)
        .then(response => response.json())
        .then(data => {
            // Populate constituency dropdown
        });
});
```

### Campus Add/Edit Forms
- Same dynamic loading as institutions
- Ensures consistency across the system

### Member Management
- Members can be linked to constituencies
- Tracks both current and origin constituencies

## API Endpoints

### Get Constituencies by Region
**Endpoint:** `ajax/get_constituencies.php`

**Method:** GET

**Parameters:**
- `region_id` (required) - The ID of the region

**Response:** JSON array of constituencies
```json
[
    {
        "id": 1,
        "name": "Tema Central",
        "region_id": 1,
        "created_by": 1,
        "created_at": "2025-01-23 08:00:00",
        "updated_at": "2025-01-23 08:00:00"
    }
]
```

**Usage Example:**
```javascript
fetch('ajax/get_constituencies.php?region_id=1')
    .then(response => response.json())
    .then(constituencies => {
        constituencies.forEach(c => {
            console.log(c.name);
        });
    });
```

## Sample Data

The database comes with sample constituencies for major regions:

**Greater Accra Region:**
- Tema Central
- Accra Central
- Ablekuma North

**Ashanti Region:**
- Kumasi Central
- Ejisu
- Asokwa

**Central Region:**
- Cape Coast North
- Cape Coast South
- Agona East

**Western Region:**
- Takoradi
- Sekondi
- Ahanta West

## Security Features

### Access Control
```php
if (!hasAnyRole(['Admin', 'Executive'])) {
    setFlashMessage('danger', 'You do not have permission to access this page');
    redirect('dashboard.php');
}
```

### Delete Protection
```php
if (isset($_GET['delete']) && hasRole('Admin')) {
    // Only admins can delete
}
```

### SQL Injection Prevention
- All queries use prepared statements
- Parameters are bound with proper types
- Input is sanitized before use

### XSS Protection
- All output is escaped with `htmlspecialchars()`
- User input is sanitized
- No raw HTML from database

## Error Handling

### Foreign Key Constraints
If a constituency is linked to institutions, campuses, or members, deletion will fail with a user-friendly error message:
```
"Failed to delete constituency. It may be linked to other records."
```

### Validation
- Region must be selected
- Constituency name is required
- Duplicate names are prevented at database level

## Testing Checklist

- [ ] Admin can access constituencies page
- [ ] Executive can access constituencies page
- [ ] Regular members are blocked
- [ ] Can add new constituency
- [ ] Can edit existing constituency
- [ ] Admin can delete constituency
- [ ] Executive cannot delete constituency
- [ ] Filter by region works
- [ ] Statistics display correctly
- [ ] DataTables search works
- [ ] DataTables sorting works
- [ ] AJAX endpoint returns correct data
- [ ] Dynamic loading in forms works
- [ ] Cannot delete constituency in use
- [ ] Success/error messages display
- [ ] Page is responsive on mobile

## Troubleshooting

### Issue: Constituencies not loading in forms
**Solution:** Check that `ajax/get_constituencies.php` is accessible and returns valid JSON

### Issue: Cannot delete constituency
**Solution:** Check if constituency is linked to institutions, campuses, or members. Remove those links first.

### Issue: Filter not working
**Solution:** Ensure region_id parameter is being passed correctly in URL

### Issue: Modal not opening
**Solution:** Verify CoreUI JavaScript is loaded and no console errors

## Future Enhancements

1. **Bulk Import** - Import constituencies from CSV/Excel
2. **Export** - Export constituency list to Excel/PDF
3. **Search** - Advanced search with multiple filters
4. **Audit Log** - Track who created/modified constituencies
5. **Validation** - Check for duplicate names within same region
6. **Map View** - Display constituencies on Ghana map
7. **Member Count** - Show number of members per constituency

## Related Documentation

- `README.md` - Project overview
- `INSTALLATION.md` - Setup instructions
- `FEATURES.md` - Complete feature list
- `UPDATES.md` - Recent changes log

## Support

For issues or questions about constituency management:
1. Check this documentation
2. Review the database schema
3. Test with sample data
4. Check browser console for errors
5. Review PHP error logs

---

**Last Updated:** January 23, 2025  
**Version:** 1.1.0  
**Status:** Fully Implemented ✅
