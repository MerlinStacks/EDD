# Changelog
All notable changes to the ED Dates CK plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.3] - 2024-03-18
### Fixed
- Missing class files causing fatal error
- Added required class files in includes directory

## [1.0.2] - 2024-03-18
### Added
- HPOS (High-Performance Order Storage) compatibility declaration
- Improved shipping method detection to include all zone-specific and global methods
- Better formatting for shipping method names in admin settings

### Changed
- Moved admin settings to its own top-level menu item
- Updated block registration to use modern WordPress block API
- Improved shipping zone handling to include inactive methods

### Fixed
- Block visibility in WordPress editor
- Shipping methods not showing all available methods
- Admin menu placement and organization

## [1.0.1] - 2024-03-17
### Added
- Initial block editor support
- Holiday management system
- Shop closed days configuration

### Changed
- Improved admin interface
- Enhanced delivery date calculations

## [1.0.0] - 2024-03-16
### Added
- Initial release
- Basic delivery date estimation
- Product lead time settings
- Shipping method transit times
- Order cutoff time settings
- Product page integration
- Cart and checkout page integration 