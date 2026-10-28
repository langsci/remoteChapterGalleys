# Remote Chapter Galleys Plugin

- Author: Ronald Steffen
- Version: 2.0.0.0

## Description

This plugin adds the possibility to the chapter tab to remotely host chapter galley files.

## Chapter numbering

Remote chapter buttons can carry a chapter number, e.g. "Chapter 3".

- **Counting:** the number of a chapter is the publication's *chapter number offset* plus its position among all chapters of the publication. Chapters ticked "Do not count this chapter" are skipped. Chapters without a remote link are counted too.
- **Label:** a button label is the *remote URL label* of the chapter. New chapters are pre-filled with the publication's *default chapter label* (default: "Chapter"). Both the offset and the default label are set via the "Set chapter number offset" action of the chapter grid.
- **Number in the form:** the read-only number field in the chapter form shows the number the chapter will get, based on the saved chapters. It is not updated live.
- **On publish:** when a publication is published, the numbers are written permanently into the labels. This only happens for chapters that have a remote URL, aren't excluded, and whose label contains the default chapter label and no digit. The label becomes e.g. "Chapter 3". The publication is then flagged (`chapterLabelsNumbered`) and the numbers are never recalculated. Later changes to the offset, chapter order or exclusions don't change the labels, and chapters added afterwards are not numbered automatically.
- **Display:** the book page shows the stored label as it is and does no counting.

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
