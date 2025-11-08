# Silver Assist - ACF Clone Fields

![Version](https://img.shields.io/badge/version-1.1.0-blue.svg)
![License](https://img.shields.io/badge/license-PolyForm%20Noncommercial-red.svg)
![PHP](https://img.shields.io/badge/php-8.2%2B-purple.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-blue.svg)
![ACF Pro](https://img.shields.io/badge/acf_pro-required-orange.svg)

Clone Advanced Custom Fields between WordPress posts with precision and confidence. Select individual fields, preview changes, and create automatic backups - all from a simple sidebar interface.

## ✨ Features

- **📋 Selective Field Cloning**: Choose exactly which fields to copy - no more all-or-nothing
- **🔄 Automatic Backups**: Every clone operation creates a restore point (v1.1.0+)
- **⚠️ Smart Conflict Detection**: See which fields will overwrite existing data before you click
- **📊 Visual Field Preview**: View field types and values before cloning
- **🎯 Same Post Type Only**: Clone safely between posts of the same type
- **⚙️ Flexible Configuration**: Enable/disable cloning per post type via Settings Hub
- **🔒 Built-in Security**: All operations include permission checks and data validation
- **🚀 Automatic Updates**: GitHub-powered updates directly in WordPress admin
- **🆓 ACF Free & Pro Compatible**: Works with both ACF versions - Pro fields available when ACF Pro is active

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 8.2 or higher
- **ACF Plugin**: Advanced Custom Fields (free) **OR** Advanced Custom Fields Pro
  - **ACF Free**: Supports basic fields (text, image, select, etc.)
  - **ACF Pro**: Adds support for advanced fields (repeater, group, flexible content, clone)

**Note**: The plugin works with either version. Pro-only field types are automatically excluded when using ACF free.

## 📦 Installation

### Method 1: WordPress Admin (Recommended)

1. Download the latest release from [GitHub Releases](https://github.com/silverassist/acf-clone-fields/releases)
2. Go to **Plugins → Add New → Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Method 2: FTP Upload

1. Download and extract the ZIP file
2. Upload the `silver-assist-acf-clone-fields` folder to `/wp-content/plugins/`
3. Activate from **WordPress Admin → Plugins**

### Method 3: WP-CLI

```bash
wp plugin install silver-assist-acf-clone-fields.zip --activate
```

## ⚙️ Configuration

### Enable Post Types

1. Navigate to **Silver Assist → Settings Hub** in WordPress admin
2. Click **ACF Clone Fields**
3. Select post types that should support field cloning
4. Configure backup retention settings (optional)
5. Save settings

### Backup Settings

- **Retention by Age**: Keep backups for X days (default: 30 days)
- **Retention by Count**: Keep last X backups per post (default: 10 backups)
- Both policies work together to manage storage

## 🚀 How to Use

### Quick Start: Clone Fields in 4 Steps

#### Step 1: Open the Clone Interface

1. Edit any post that belongs to an enabled post type
2. Find **"Clone Custom Fields"** meta box in the sidebar (right panel)
3. Click **"Clone Fields from Another Post"**

#### Step 2: Select Source Post

- Choose the post you want to copy fields from
- View field statistics for each available post
- Click **Continue** to proceed

#### Step 3: Select Fields to Clone

- **Individual Selection**: Check specific fields you want to copy
- **Group Selection**: Use "Select All" to choose entire field groups
- **Preview Information**: 
  - ✅ Green indicators show fields with values
  - ⚠️ Yellow warnings show fields that will overwrite existing data
  - 📊 Field type and current value displayed

#### Step 4: Configure & Execute

- **Backup Option**: Automatically enabled - creates a restore point before cloning
- **Overwrite Settings**: Choose to replace existing values or preserve them
- Click **"Clone Selected Fields"** to execute
- View success confirmation with count of cloned fields

### Managing Backups

The **Backups** tab in the meta box shows:
- List of all backup points for the current post
- Creation date and cloned field count
- **Restore**: Revert to a previous backup point
- **Delete**: Remove individual backups
- **Cleanup**: Apply retention policies manually

## 💡 Use Cases

### Content Migration
Clone product specifications from a template post to new products

### Bulk Updates
Update common fields across multiple posts by cloning from a master post

### Content Duplication
Create new posts with similar ACF data without manual reentry

### Template Posts
Maintain template posts and clone their structure to new content

## 🔒 Security & Privacy

- **Permission Checks**: Only users with `edit_posts` capability can clone fields
- **Nonce Verification**: All operations secured with WordPress nonces
- **Data Validation**: Input sanitization and output escaping on all operations
- **Audit Trail**: Optional logging of clone operations for review

## ❓ Troubleshooting

### "No Source Posts Available"

**Problem**: Can't find posts to clone from

**Solution**:
1. Verify you have other posts of the same post type
2. Check that ACF fields are assigned to those posts
3. Confirm post type is enabled in Settings Hub
4. Ensure you have edit permissions for the post type

### "Clone Operation Failed"

**Problem**: Fields didn't copy

**Solution**:
1. Check browser console for errors (F12 → Console tab)
2. Verify ACF Pro is active and up to date
3. Ensure source post has values in selected fields
4. Check WordPress debug.log for detailed errors

### "Modal Won't Open"

**Problem**: Nothing happens when clicking clone button

**Solution**:
1. Clear browser cache and hard reload (Ctrl+Shift+R or Cmd+Shift+R)
2. Check browser console for JavaScript errors
3. Test for plugin conflicts by temporarily disabling other plugins
4. Verify jQuery is loaded on the page

### "Backups Not Creating"

**Problem**: No backup created before clone operation

**Solution**:
1. Check database table `wp_acf_field_backups` exists
2. Verify database user has CREATE and INSERT permissions
3. Check WordPress debug.log for database errors
4. Increase PHP memory limit if working with large field sets

### Need More Help?

- **Debug Mode**: Enable `WP_DEBUG` and `WP_DEBUG_LOG` in `wp-config.php`
- **GitHub Issues**: [Report bugs or request features](https://github.com/silverassist/acf-clone-fields/issues)
- **Documentation**: See `CONTRIBUTING.md` for developer documentation

## 🔄 Automatic Updates

The plugin includes automatic update functionality via GitHub releases. When new versions are published, you'll see update notifications in your WordPress admin dashboard just like any other plugin.

## 📄 License

Licensed under the [PolyForm Noncommercial License 1.0.0](https://polyformproject.org/licenses/noncommercial/1.0.0/).

- ✅ **Free**: For personal, educational, and noncommercial use
- ❌ **Commercial Use**: Requires a separate license

## 👨‍💻 For Developers

Want to contribute or extend the plugin? See **[CONTRIBUTING.md](CONTRIBUTING.md)** for:
- Development setup and coding standards
- Testing guidelines and CI/CD workflows
- Architecture documentation
- Extension hooks and filters
- Release process

---

Made with ❤️ by [Silver Assist](https://github.com/SilverAssist)
