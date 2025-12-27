<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<tr class="group-header" :class="{ collapsed: !expanded }">
		<!-- Group name cell with colored left border (Monday.com style) -->
		<td class="group-header-cell sticky" :style="borderStyle" @click="$emit('toggle')">
			<div class="group-header-content">
				<button class="expand-btn" :aria-label="expanded ? t('tablespro', 'Collapse') : t('tablespro', 'Expand')">
					<ChevronRightIcon v-if="!expanded" :size="18" />
					<ChevronDownIcon v-else :size="18" />
				</button>

				<span class="group-label" :style="{ color: effectiveColor }">
					{{ label }}
				</span>

				<span v-if="!expanded && rows.length > 0" class="group-count">
					{{ rows.length }} {{ rows.length === 1 ? t('tablespro', 'item') : t('tablespro', 'items') }}
				</span>
			</div>
		</td>

		<!-- Collapsed: show summary for each column aligned under column headers -->
		<template v-if="!expanded">
			<td v-for="column in columns"
				:key="'collapsed-' + column.id"
				class="collapsed-summary-cell"
				@click="$emit('toggle')">
				<CollapsedSummary
					v-if="shouldShowSummary(column)"
					:column="column"
					:rows="rows" />
			</td>
			<td class="collapsed-summary-cell sticky-end" @click="$emit('toggle')" />
		</template>

		<!-- Expanded: just fill remaining space -->
		<template v-else>
			<td :colspan="columns.length + 1" class="group-header-spacer" @click="$emit('toggle')" />
		</template>
	</tr>
</template>

<script>
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'
import ChevronDownIcon from 'vue-material-design-icons/ChevronDown.vue'
import { translate as t } from '@nextcloud/l10n'
import CollapsedSummary from './CollapsedSummary.vue'

export default {
	name: 'GroupHeader',

	components: {
		ChevronRightIcon,
		ChevronDownIcon,
		CollapsedSummary,
	},

	props: {
		label: {
			type: String,
			required: true,
		},
		rows: {
			type: Array,
			default: () => [],
		},
		columns: {
			type: Array,
			default: () => [],
		},
		expanded: {
			type: Boolean,
			default: true,
		},
		groupColor: {
			type: String,
			default: null,
		},
	},

	computed: {
		effectiveColor() {
			return this.groupColor || '#0073ea'
		},

		borderStyle() {
			return {
				borderLeft: `4px solid ${this.effectiveColor}`,
			}
		},
	},

	methods: {
		t,

		shouldShowSummary(column) {
			// Only show summary for number and selection columns
			return ['number', 'number-stars', 'number-progress', 'selection', 'selection-multi'].includes(column.type)
		},
	},
}
</script>

<style lang="scss" scoped>
.group-header {
	cursor: pointer;
	user-select: none;
	background-color: var(--color-main-background) !important;
	transition: background-color 0.15s ease;

	&:hover {
		background-color: var(--color-background-hover) !important;
	}
}

.group-header-cell {
	padding: 12px 16px 12px 12px !important;
	border: none !important;
	border-bottom: 1px solid var(--color-border-dark) !important;
	background-color: inherit;
	white-space: nowrap;

	&.sticky {
		position: sticky;
		inset-inline-start: 0;
		z-index: 5;
		background-color: var(--color-main-background);
	}
}

.group-header-spacer {
	border: none !important;
	border-bottom: 1px solid var(--color-border-dark) !important;
	background-color: inherit;
}

.group-header-content {
	display: flex;
	align-items: center;
	gap: 10px;
}

.expand-btn {
	background: none;
	border: none;
	padding: 4px;
	margin: -4px;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 4px;
	color: var(--color-text-maxcontrast);
	transition: all 0.15s ease;
	flex-shrink: 0;

	&:hover {
		background-color: var(--color-background-dark);
		color: var(--color-main-text);
	}
}

.group-label {
	font-weight: 600;
	font-size: 15px;
	letter-spacing: -0.01em;
}

.group-count {
	color: var(--color-text-maxcontrast);
	font-size: 13px;
	font-weight: 400;
	margin-inline-start: 4px;
}

.collapsed-summary-cell {
	padding: 12px 8px !important;
	border: none !important;
	border-bottom: 1px solid var(--color-border-dark) !important;
	background-color: inherit;
	vertical-align: middle;

	&.sticky-end {
		position: sticky;
		inset-inline-end: 0;
		background-color: var(--color-main-background);
	}
}

// Hover effect for entire row - update sticky cells too
.group-header:hover {
	.group-header-cell.sticky,
	.collapsed-summary-cell.sticky-end {
		background-color: var(--color-background-hover);
	}
}
</style>
