<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<tr class="group-summary">
		<td class="sticky" />
		<td v-for="column in columns" :key="'gsummary-' + column.id" class="group-summary-cell">
			<SummaryCell :column="column" :rows="rows" />
		</td>
		<td class="sticky" />
	</tr>
</template>

<script>
import SummaryCell from './SummaryCell.vue'

export default {
	name: 'GroupSummary',

	components: {
		SummaryCell,
	},

	props: {
		columns: {
			type: Array,
			default: () => [],
		},
		rows: {
			type: Array,
			default: () => [],
		},
	},
}
</script>

<style lang="scss" scoped>
.group-summary {
	background-color: var(--color-background-dark) !important;
	border-bottom: 2px solid var(--color-border);

	td {
		padding: 8px !important;
		border-top: 1px solid var(--color-border);
	}

	.group-summary-cell {
		border: none !important;
	}
}
</style>
