# Remote Chapter Galleys Plugin

- Author: Ronald Steffen
- Version: 2.0.0.0

## Description

This plugin adds the possibility to the chapter tab to remotely host chapter galley files.

## License

Copyright (c) 2021 Language Science Press

Distributed under the GNU GPL v3. For full terms see the file docs/LICENSE.

## System Requirements

This plugin is compatible with...

- OMP 3.5
- No other plugin is required. The remote galleys are appended to the book page through the `Templates::Catalog::Book::Main` hook. The plugin overrides only the chapter form template (`controllers/grid/users/chapter/form/chapterForm.tpl`); re-diff it against core on every OMP upgrade.

## Installation

To install the plugin:

- Upload the tar.gz file in OMP (Management > Website Settings > Plugins > Generic Plugins), or
- install via Github: <https://github.com/langsci/remoteChapterGalleys>
