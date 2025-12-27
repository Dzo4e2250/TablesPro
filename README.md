# TablesPro

An advanced fork of [Nextcloud Tables](https://github.com/nextcloud/tables) with Monday.com-like features.

## New Features (Planned)

- **Row Groups** - Collapsible sections to organize rows
- **Summary Rows** - SUM, COUNT, percentage statistics at group level
- **Colored Status** - Color-coded selection/dropdown options
- **Progress Visualization** - Visual progress bars for status columns

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
