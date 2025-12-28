# TablesPro

An advanced fork of [Nextcloud Tables](https://github.com/nextcloud/tables) with Monday.com-inspired design and enhanced features.

## About

TablesPro is a fork of the official Nextcloud Tables app, enhanced with modern UI/UX improvements inspired by Monday.com. The goal is to provide a more polished and feature-rich table management experience within Nextcloud.

## Implemented Features

- **Compact Row Design** - Thinner, more compact table rows for better data density
- **Summary Rows** - Automatic aggregations (SUM, AVG, MIN, MAX, COUNT) for number and selection columns
- **Item Count Display** - Shows row count in summary rows
- **Column Resize** - Drag-to-resize columns with persistent width saved per table (localStorage)
- **Progress Bars in Summaries** - Visual percentage indicators for selection columns
- **Activity Integration** - Row-level activity tracking

## Planned Features

- **Row Groups** - Collapsible sections to organize rows
- **Colored Status Options** - Color-coded selection/dropdown options
- **Enhanced Progress Visualization** - More visual progress indicators

## Installation

1. Download the latest release
2. Extract to your Nextcloud `apps/` directory
3. Enable the app in Nextcloud admin settings

## Development

```bash
# Install dependencies
composer install --no-dev
npm ci

# Build for development
npm run dev

# Build for production
npm run build
```

## License

AGPL-3.0-or-later

## Credits

Based on [Nextcloud Tables](https://github.com/nextcloud/tables) by Nextcloud GmbH.
