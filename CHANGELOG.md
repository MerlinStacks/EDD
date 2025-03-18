# Changelog
All notable changes to the ED Dates CK plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.10] - 2024-03-18
### Fixed
- Syntax error in blocks class causing PHP parse error
- Removed duplicate method definition in block renderer

## [1.0.9] - 2024-03-18
### Added
- Enhanced block editor support with customizable delivery date display
- New block settings for display style (Default, Compact, Prominent)
- Optional calendar icon with position control
- Border style options (Left Accent, Full Border, No Border)
- Color customization for text and background
- Font size controls and typography settings
- Spacing controls for margin and padding
- Dark mode support for block display
- RTL language support for block layout
- Responsive design for all screen sizes

### Changed
- Improved block preview in editor
- Enhanced block rendering with better error handling
- Updated block styles for better visual consistency
- Improved accessibility in block display

## [1.0.8] - 2024-03-18
### Fixed
- PHP Warning when accessing shipping method array indices
- Added proper array checks in delivery date calculator
- Improved error handling for shipping method settings
### Added
- Comprehensive error handling for DateTime operations
- Type safety checks for all numeric inputs
- Validation for lead time and shipping day ranges
- Error logging for debugging purposes
- Improved AJAX error responses with meaningful messages
- Added step attribute to number inputs
- Added tooltips for lead time fields
### Changed
- Improved handling of invalid option values
- Better fallback behavior for error cases
- Enhanced nonce verification in AJAX handlers
- Improved validation of product and post data
- Better error messages for users and developers

## [1.0.7] - 2024-03-18
### Fixed
- Block editor integration completely rebuilt for better compatibility
- Added proper block registration and rendering class
- Fixed block preview in product editor
- Added proper block styles and warning messages
- Improved block build process and file organization

## [1.0.6] - 2024-03-18
### Changed
- Improved holiday date picker to support true multiple date selection
- Enhanced admin settings tabs to load content instantly
- Updated block editor integration with better preview and warnings

### Fixed
- Holiday date picker not allowing multiple date selection
- Admin settings tabs causing unnecessary page reloads
- Block editor integration and visibility issues

## [1.0.5] - 2024-03-18
### Added
- Calendar picker for shop and postage holidays
- Grid layout for shipping methods settings
- Improved admin interface with tabbed navigation

### Changed
- Moved shipping methods to a separate settings tab
- Enhanced holiday date selection with jQuery UI Datepicker
- Improved block editor integration and preview

### Fixed
- Block not appearing in the WordPress editor
- Holiday date selection buttons not working
- Admin interface layout and styling issues

## [1.0.4] - 2024-03-18
### Fixed
- Admin menu not appearing in WordPress dashboard
- Added proper initialization of admin and product classes

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