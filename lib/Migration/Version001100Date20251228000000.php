<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Add view_type and grouping_column_id columns to tables_views for Board view support
 */
class Version001100Date20251228000000 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('tables_views');

		// Add view_type column (table, board)
		if (!$table->hasColumn('view_type')) {
			$table->addColumn('view_type', Types::STRING, [
				'notnull' => true,
				'length' => 20,
				'default' => 'table',
			]);
		}

		// Add grouping_column_id for board views
		if (!$table->hasColumn('grouping_column_id')) {
			$table->addColumn('grouping_column_id', Types::INTEGER, [
				'notnull' => false,
				'default' => null,
			]);
		}

		// Add card_title_column_id for board views (which column to use as card title)
		if (!$table->hasColumn('card_title_column_id')) {
			$table->addColumn('card_title_column_id', Types::INTEGER, [
				'notnull' => false,
				'default' => null,
			]);
		}

		return $schema;
	}
}
