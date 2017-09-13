# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Fixed
- DF-1199, DF-1210 Fixed creating files with POST /files/{file_path} api

## [0.4.0] - 2017-08-17
### Changed
- Reworking API doc usage and generation

## [0.3.0] - 2017-07-27
### Fixed
- DF-1144 Made DELETE behavior consistent across local and all remote file services
- Fixed file upload issue for FTP, SFTP, and WebDAV
- Do not calculate path on container level files
### Added
- DF-580 Added support for FTP, SFTP, FTPS
- DF-276 Added support for WebDAV

## [0.2.1] - 2017-06-21
### Changed
- Local file service config now accepts paths relative to the storage folder as well as full system paths.

## [0.2.0] - 2017-06-05
### Changed
- Cleanup - removal of php-utils dependency
- Remove usage of internal config for local file system service
### Fixed
- DF-1134 Fixed local file service path mapping

## 0.1.0 - 2017-04-21
### Added
- DF-975 Enabled filename change on upload via URL
- DF-976 Added searching feature for file services

First official release working with the new [dreamfactory](https://github.com/dreamfactorysoftware/dreamfactory) project.

[Unreleased]: https://github.com/dreamfactorysoftware/df-file/compare/0.4.0...HEAD
[0.4.0]: https://github.com/dreamfactorysoftware/df-file/compare/0.3.0...0.4.0
[0.3.0]: https://github.com/dreamfactorysoftware/df-file/compare/0.2.1...0.3.0
[0.2.1]: https://github.com/dreamfactorysoftware/df-file/compare/0.2.0...0.2.1
[0.2.0]: https://github.com/dreamfactorysoftware/df-file/compare/0.1.0...0.2.0
