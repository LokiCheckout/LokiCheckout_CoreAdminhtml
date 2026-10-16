# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Fixed
- Respect ACL resources in admin controllers instead of always allowing access
- Add parent ACL resource `LokiCheckout_CoreAdminhtml::index` used by the admin menu
- Prevent duplicate check results when a system check is executed more than once
- Do not break the Module Overview when the Composer API is unreachable
- Read git info from `.git/HEAD` (plus refs, packed refs and worktrees) instead of `.git/FETCH_HEAD`
- Shorten git commits to their first 8 characters instead of their last 8
- Compare module versions with `version_compare()` and show unknown versions as warning instead of error or notice
- Fall back to the latest Composer version for dev-versions without a known latest commit

### Added
- Unit, integration and Playwright tests

## [1.0.12] - 08 April 2026
### Fixed
- Compare git commits in module version when working with dev-versions
- Show commit with branch name

## [1.0.11] - 18 February 2026
### Fixed
- Temporarily disable commit-versioning in Admin module listing

## [1.0.10] - 17 February 2026
### Fixed
- Enhance tooltip for registration-column

## [1.0.9] - 12 January 2026
### Fixed
- Fix escaping
- Copy generic CI/CD files

## [1.0.8] - 12 December 2025
### Fixed
- Add option for useful URL per check result
- Add plan-trial keyword to composer file
- Update composer keywords
- Update composer keywords
- Update composer keywords
- Update README

## [1.0.7] - 28 August 2025
### Fixed
- Document latest version of template
- Add MODULE.json
- Prepare release

## [1.0.6] - 27 August 2025
### Fixed
- Add CI files
- Make sure to list any module prefixed with LokiCheckout_ or Loki_
- Replace yireo/opensearch with yireo/opensearch-dummy in Gitlab CI

## [1.0.5] - 21 August 2025
### Fixed
- Add dependency with loki/magento2-css-utils
- Declare used PHP namespaces
- Document latest version of template

## [1.0.4] - 18 August 2025
### Fixed
- Add config option to hide registration-column
- Fix look and feel of module overview under Mage-OS Admin Theme

## [1.0.3] - 07 August 2025
### Fixed
- Lower PHP requirement to PHP 8.2+

## [1.0.2] - 01 August 2025
### Fixed
- Make strings translatable

## [1.0.1] - 22 July 2025
### Fixed
- Fix PSR-4 namespace

## [1.0.0] - 22 July 2025
### Fixed
- Move files from core module
- Init files
- Adding all files to git via Yireo Command

