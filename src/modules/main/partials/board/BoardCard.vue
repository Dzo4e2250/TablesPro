<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="board-card" :class="{ 'board-card--dragging': dragging }" @click="$emit('click', row)">
		<div class="board-card__header">
			<h4 class="board-card__title">{{ cardTitle }}</h4>
			<NcButton v-if="canEdit"
				type="tertiary"
				class="board-card__menu"
				:aria-label="t('tablespro', 'Card options')"
				@click.stop="$emit('edit', row)">
				<template #icon>
					<DotsHorizontal :size="20" />
				</template>
			</NcButton>
		</div>

		<!-- Labels from selection columns -->
		<div v-if="visibleLabels.length > 0" class="board-card__labels">
			<span v-for="label in visibleLabels"
				:key="label.columnId + '-' + label.value"
				class="board-card__label"
				:style="getLabelStyle(label)">
				{{ label.value }}
			</span>
		</div>

		<!-- Progress bar for number-progress columns -->
		<div v-if="progressValue !== null" class="board-card__progress">
			<div class="board-card__progress-bar" :style="{ width: progressValue + '%' }" />
			<span class="board-card__progress-text">{{ progressValue }}%</span>
		</div>

		<!-- Badges section -->
		<div v-if="hasBadges" class="board-card__badges">
			<!-- Due date -->
			<span v-if="dueDate"
				class="board-card__badge board-card__badge--date"
				:class="{ 'board-card__badge--overdue': isOverdue, 'board-card__badge--due-soon': isDueSoon }">
				<CalendarIcon :size="16" />
				{{ formattedDueDate }}
			</span>

			<!-- Assigned users -->
			<div v-if="assignedUsers.length > 0" class="board-card__assignees">
				<NcAvatar v-for="user in assignedUsers.slice(0, 3)"
					:key="user.id"
					:user="user.id"
					:display-name="user.displayName"
					:size="24"
					:show-user-status="false" />
				<span v-if="assignedUsers.length > 3" class="board-card__assignees-more">
					+{{ assignedUsers.length - 3 }}
				</span>
			</div>

			<!-- Checklist progress -->
			<span v-if="checklistProgress" class="board-card__badge board-card__badge--checklist">
				<CheckIcon :size="16" />
				{{ checklistProgress.done }}/{{ checklistProgress.total }}
			</span>

			<!-- Comments count -->
			<span v-if="commentsCount > 0" class="board-card__badge">
				<CommentIcon :size="16" />
				{{ commentsCount }}
			</span>

			<!-- Attachments count -->
			<span v-if="attachmentsCount > 0" class="board-card__badge">
				<AttachmentIcon :size="16" />
				{{ attachmentsCount }}
			</span>
		</div>
	</div>
</template>

<script>
import { NcButton, NcAvatar } from '@nextcloud/vue'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'
import CalendarIcon from 'vue-material-design-icons/Calendar.vue'
import CheckIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'
import CommentIcon from 'vue-material-design-icons/CommentOutline.vue'
import AttachmentIcon from 'vue-material-design-icons/Paperclip.vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'BoardCard',

	components: {
		NcButton,
		NcAvatar,
		DotsHorizontal,
		CalendarIcon,
		CheckIcon,
		CommentIcon,
		AttachmentIcon,
	},

	props: {
		row: {
			type: Object,
			required: true,
		},
		columns: {
			type: Array,
			default: () => [],
		},
		titleColumnId: {
			type: Number,
			default: null,
		},
		groupingColumnId: {
			type: Number,
			default: null,
		},
		canEdit: {
			type: Boolean,
			default: false,
		},
		dragging: {
			type: Boolean,
			default: false,
		},
	},

	computed: {
		cardTitle() {
			// Use title column if specified
			if (this.titleColumnId) {
				const cell = this.row.data?.find(d => d.columnId === this.titleColumnId)
				if (cell?.value) {
					return String(cell.value).substring(0, 100)
				}
			}

			// Otherwise find first text column
			const textColumn = this.columns.find(c =>
				c.type === 'text-line' || c.type === 'text-rich',
			)
			if (textColumn) {
				const cell = this.row.data?.find(d => d.columnId === textColumn.id)
				if (cell?.value) {
					// Strip HTML if rich text
					const value = String(cell.value).replace(/<[^>]*>/g, '')
					return value.substring(0, 100)
				}
			}

			// Fallback to row ID
			return `#${this.row.id}`
		},

		visibleLabels() {
			const labels = []
			const excludeTypes = ['text-line', 'text-rich', 'text-link']

			for (const column of this.columns) {
				// Skip grouping column and text columns
				if (column.id === this.groupingColumnId) continue
				if (column.id === this.titleColumnId) continue
				if (excludeTypes.includes(column.type)) continue
				if (column.id < 0) continue // Skip meta columns

				// Only show selection columns as labels
				if (column.type === 'selection' || column.type === 'selection-multi') {
					const cell = this.row.data?.find(d => d.columnId === column.id)
					if (cell?.value) {
						const values = Array.isArray(cell.value) ? cell.value : [cell.value]
						for (const val of values) {
							if (val) {
								const option = this.getSelectionOption(column, val)
								labels.push({
									columnId: column.id,
									value: option?.label || val,
									color: option?.color || null,
								})
							}
						}
					}
				}
			}

			return labels.slice(0, 4) // Limit to 4 labels
		},

		// Due date from datetime columns
		dueDate() {
			const dateColumn = this.columns.find(c =>
				c.type === 'datetime' || c.type === 'datetime-date',
			)
			if (!dateColumn) return null

			const cell = this.row.data?.find(d => d.columnId === dateColumn.id)
			if (!cell?.value) return null

			try {
				return new Date(cell.value)
			} catch {
				return null
			}
		},

		isOverdue() {
			if (!this.dueDate) return false
			const now = new Date()
			now.setHours(0, 0, 0, 0)
			return this.dueDate < now
		},

		isDueSoon() {
			if (!this.dueDate || this.isOverdue) return false
			const now = new Date()
			const tomorrow = new Date(now)
			tomorrow.setDate(tomorrow.getDate() + 2)
			return this.dueDate <= tomorrow
		},

		formattedDueDate() {
			if (!this.dueDate) return ''

			const now = new Date()
			const diffDays = Math.ceil((this.dueDate - now) / (1000 * 60 * 60 * 24))

			if (diffDays === 0) return t('tablespro', 'Today')
			if (diffDays === 1) return t('tablespro', 'Tomorrow')
			if (diffDays === -1) return t('tablespro', 'Yesterday')
			if (diffDays < -1) return t('tablespro', '{days} days ago', { days: Math.abs(diffDays) })
			if (diffDays < 7) return t('tablespro', 'In {days} days', { days: diffDays })

			return this.dueDate.toLocaleDateString()
		},

		// Progress from number-progress columns
		progressValue() {
			const progressColumn = this.columns.find(c => c.type === 'number-progress')
			if (!progressColumn) return null

			const cell = this.row.data?.find(d => d.columnId === progressColumn.id)
			if (cell?.value === null || cell?.value === undefined) return null

			return Math.min(100, Math.max(0, Number(cell.value)))
		},

		// Assigned users from usergroup columns
		assignedUsers() {
			const users = []
			const usergroupColumns = this.columns.filter(c => c.type === 'usergroup')

			for (const column of usergroupColumns) {
				const cell = this.row.data?.find(d => d.columnId === column.id)
				if (cell?.value) {
					const values = Array.isArray(cell.value) ? cell.value : [cell.value]
					for (const val of values) {
						if (val && typeof val === 'object' && val.id) {
							users.push({
								id: val.id,
								displayName: val.displayName || val.id,
								type: val.type || 'user',
							})
						} else if (val && typeof val === 'string') {
							users.push({
								id: val,
								displayName: val,
								type: 'user',
							})
						}
					}
				}
			}

			return users
		},

		// Checklist from text-rich columns (markdown checkboxes)
		checklistProgress() {
			const richTextColumn = this.columns.find(c => c.type === 'text-rich')
			if (!richTextColumn) return null

			const cell = this.row.data?.find(d => d.columnId === richTextColumn.id)
			if (!cell?.value) return null

			const content = String(cell.value)
			const checkboxPattern = /\[([ xX])\]/g
			const matches = [...content.matchAll(checkboxPattern)]

			if (matches.length === 0) return null

			const done = matches.filter(m => m[1].toLowerCase() === 'x').length
			return { done, total: matches.length }
		},

		// Comments count (placeholder - requires backend)
		commentsCount() {
			return this.row.commentsCount || 0
		},

		// Attachments count (placeholder - requires backend)
		attachmentsCount() {
			return this.row.attachmentsCount || 0
		},

		hasBadges() {
			return this.dueDate
				|| this.assignedUsers.length > 0
				|| this.checklistProgress
				|| this.commentsCount > 0
				|| this.attachmentsCount > 0
		},
	},

	methods: {
		t,

		getSelectionOption(column, value) {
			const options = column.selectionOptions || []
			return options.find(opt => opt.id === value || opt.label === value)
		},

		getLabelStyle(label) {
			if (label.color) {
				return {
					backgroundColor: label.color,
					color: this.getContrastColor(label.color),
				}
			}
			return {}
		},

		getContrastColor(hexColor) {
			// Remove # if present
			const hex = hexColor.replace('#', '')
			const r = parseInt(hex.substr(0, 2), 16)
			const g = parseInt(hex.substr(2, 2), 16)
			const b = parseInt(hex.substr(4, 2), 16)
			const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
			return luminance > 0.5 ? '#000000' : '#ffffff'
		},
	},
}
</script>

<style lang="scss" scoped>
.board-card {
	background-color: var(--color-main-background);
	border: 2px solid var(--color-border-dark);
	border-radius: var(--border-radius-large);
	padding: calc(var(--default-grid-baseline) * 2);
	cursor: pointer;
	transition: border-color 0.15s ease, box-shadow 0.15s ease;

	&:hover {
		border-color: var(--color-border-maxcontrast);
	}

	&:focus-visible {
		outline: 2px solid var(--color-primary-element);
		outline-offset: 2px;
	}

	&--dragging {
		opacity: 0.8;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
	}
}

.board-card__header {
	display: flex;
	align-items: flex-start;
	gap: var(--default-grid-baseline);
}

.board-card__title {
	flex: 1;
	margin: 0;
	font-size: 14px;
	font-weight: normal;
	line-height: 1.4;
	word-wrap: break-word;
	overflow-wrap: break-word;
}

.board-card__menu {
	flex-shrink: 0;
	margin: -8px -8px 0 0;
	opacity: 0;
	transition: opacity 0.15s ease;

	.board-card:hover &,
	.board-card:focus-within & {
		opacity: 1;
	}
}

.board-card__labels {
	display: flex;
	flex-wrap: wrap;
	gap: calc(var(--default-grid-baseline) * 1);
	margin-top: calc(var(--default-grid-baseline) * 2);
}

.board-card__label {
	display: inline-block;
	padding: 2px 8px;
	font-size: 11px;
	font-weight: 500;
	border-radius: var(--border-radius-pill);
	background-color: var(--color-primary-element-light);
	color: var(--color-primary-element);
	max-width: 120px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

// Progress bar
.board-card__progress {
	margin-top: calc(var(--default-grid-baseline) * 2);
	display: flex;
	align-items: center;
	gap: 8px;
	background-color: var(--color-background-dark);
	border-radius: var(--border-radius-pill);
	height: 6px;
	position: relative;
	overflow: hidden;
}

.board-card__progress-bar {
	height: 100%;
	background-color: var(--color-primary-element);
	border-radius: var(--border-radius-pill);
	transition: width 0.3s ease;
}

.board-card__progress-text {
	position: absolute;
	right: -30px;
	font-size: 10px;
	color: var(--color-text-maxcontrast);
}

// Badges
.board-card__badges {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 2);
	margin-top: calc(var(--default-grid-baseline) * 2);
	color: var(--color-text-maxcontrast);
	font-size: 12px;
}

.board-card__badge {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	padding: 2px 6px;
	border-radius: var(--border-radius);
	background-color: var(--color-background-dark);

	&--date {
		color: var(--color-text-maxcontrast);
	}

	&--overdue {
		background-color: var(--color-error);
		color: white;
	}

	&--due-soon {
		background-color: var(--color-warning);
		color: var(--color-warning-text);
	}

	&--checklist {
		color: var(--color-success);
	}
}

// Assigned users
.board-card__assignees {
	display: flex;
	align-items: center;
	margin-left: auto;

	:deep(.avatardiv) {
		margin-left: -8px;
		border: 2px solid var(--color-main-background);

		&:first-child {
			margin-left: 0;
		}
	}
}

.board-card__assignees-more {
	margin-left: 4px;
	font-size: 11px;
	color: var(--color-text-maxcontrast);
}
</style>
