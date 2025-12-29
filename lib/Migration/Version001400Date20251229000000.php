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
 * Add labels tables for Deck-like label functionality
 */
class Version001400Date20251229000000 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		// Create labels table
		if (!$schema->hasTable('tables_labels')) {
			$table = $schema->createTable('tables_labels');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('table_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('title', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('color', Types::STRING, [
				'notnull' => true,
				'length' => 7,
				'default' => '#0082c9',
			]);
			$table->addColumn('created_by', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('created_at', Types::DATETIME, [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['table_id'], 'tables_label_table_idx');
		}

		// Create row_labels junction table (many-to-many)
		if (!$schema->hasTable('tables_row_labels')) {
			$table = $schema->createTable('tables_row_labels');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('row_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('label_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 8,
				'unsigned' => true,
			]);
			$table->addColumn('created_at', Types::DATETIME, [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['row_id'], 'tables_rowlabel_row_idx');
			$table->addIndex(['label_id'], 'tables_rowlabel_label_idx');
			$table->addUniqueIndex(['row_id', 'label_id'], 'tables_rowlabel_unique');
		}

		return $schema;
	}
}
