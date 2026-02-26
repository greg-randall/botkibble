## Releasing Updates to WordPress.org

### One-time setup
```bash
svn checkout https://plugins.svn.wordpress.org/botkibble botkibble-svn
```

### For each release

1. Update `Stable tag` in both `readme.txt` and the plugin header in `botkibble.php` to the new version number
2. Add a changelog entry to `readme.txt`
3. Copy updated plugin files into SVN trunk:
```bash
cp -r botkibble/* /path/to/botkibble-svn/trunk/
```
4. Create a tag (replace `1.2.1` with new version):
```bash
cd botkibble-svn
svn add trunk/* --force
svn copy trunk/ tags/1.2.1/
svn commit -m "Release 1.2.1" --username gregrandall
```

### Notes
- `trunk/` = what ships to users — only *contents* of the `botkibble.zip`, not the whole repo
- `assets/` = plugin icon and banner images (not in trunk)
  - `icon.svg` — plugin icon
- WordPress.org plugin page: https://wordpress.org/plugins/botkibble
- SVN URL: https://plugins.svn.wordpress.org/botkibble