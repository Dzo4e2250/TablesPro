<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog v-if="showModal"
		class="card-detail-modal"
		:name="cardTitle"
		size="large"
		@closing="close">
		<div class="card-detail">
			<!-- Header with labels -->
			<div v-if="labels.length > 0" class="card-detail__labels">
				<span v-for="label in labels"
					:key="label.columnId + '-' + label.value"
					class="card-detail__label"
					:style="getLabelStyle(label)">
					{{ label.value }}
				</span>
			</div>

			<!-- Tabs navigation -->
			<div class="card-detail__tabs">
				<NcButton v-for="tab in tabs"
					:key="tab.id"
					:type="activeTab === tab.id ? 'primary' : 'tertiary'"
					@click="activeTab = tab.id">
					<template #icon>
						<component :is="tab.icon" :size="20" />
					</template>
					{{ tab.label }}
					<span v-if="tab.count > 0" class="tab-count">{{ tab.count }}</span>
				</NcButton>
			</div>

			<!-- Tab content -->
			<div class="card-detail__content">
				<!-- Details Tab -->
				<div v-if="activeTab === 'details'" class="tab-details">
					<!-- Due date -->
					<div v-if="dueDate" class="detail-item">
						<div class="detail-item__label">
							<CalendarIcon :size="20" />
							{{ t('tablespro', 'Due date') }}
						</div>
						<div class="detail-item__value"
							:class="{ 'overdue': isOverdue, 'due-soon': isDueSoon }">
							{{ formattedDueDate }}
						</div>
					</div>

					<!-- Assigned users -->
					<div v-if="assignedUsers.length > 0" class="detail-item">
						<div class="detail-item__label">
							<AccountIcon :size="20" />
							{{ t('tablespro', 'Assigned') }}
						</div>
						<div class="detail-item__value assigned-users">
							<div v-for="user in assignedUsers" :key="user.id" class="assigned-user">
								<NcAvatar :user="user.id"
									:display-name="user.displayName"
									:size="28"
									:show-user-status="false" />
								<span>{{ user.displayName }}</span>
							</div>
						</div>
					</div>

					<!-- Progress -->
					<div v-if="progressValue !== null" class="detail-item">
						<div class="detail-item__label">
							<ProgressIcon :size="20" />
							{{ t('tablespro', 'Progress') }}
						</div>
						<div class="detail-item__value">
							<NcProgressBar :value="progressValue" />
							<span class="progress-text">{{ progressValue }}%</span>
						</div>
					</div>

					<!-- Checklist -->
					<div v-if="checklistProgress" class="detail-item">
						<div class="detail-item__label">
							<ChecklistIcon :size="20" />
							{{ t('tablespro', 'Checklist') }}
						</div>
						<div class="detail-item__value">
							{{ checklistProgress.done }} / {{ checklistProgress.total }}
						</div>
					</div>

					<!-- Description / Rich text content -->
					<div v-if="description" class="detail-item detail-item--full">
						<div class="detail-item__label">
							<TextIcon :size="20" />
							{{ t('tablespro', 'Description') }}
						</div>
						<div class="detail-item__value description" v-html="description" />
					</div>

					<!-- All fields -->
					<div class="detail-fields">
						<h3>{{ t('tablespro', 'All fields') }}</h3>
						<div v-for="column in nonMetaColumns" :key="column.id" class="field-item">
							<div class="field-item__label">{{ column.title }}</div>
							<div class="field-item__value">
								<ColumnFormComponent
									v-if="canEdit"
									:column="column"
									:value.sync="localRow[column.id]"
									@update:value="onFieldChange(column.id, $event)" />
								<span v-else>{{ getDisplayValue(column) }}</span>
							</div>
						</div>
					</div>
				</div>

				<!-- Comments Tab -->
				<div v-else-if="activeTab === 'comments'" class="tab-comments">
					<div class="comments-list">
						<div v-if="loadingComments" class="loading">
							<NcLoadingIcon :size="32" />
						</div>
						<div v-else-if="comments.length === 0" class="empty-state">
							<CommentIcon :size="48" />
							<p>{{ t('tablespro', 'No comments yet') }}</p>
						</div>
						<div v-else>
							<div v-for="comment in comments" :key="comment.id" class="comment">
								<NcAvatar :user="comment.userId"
									:display-name="comment.userDisplayName"
									:size="32" />
								<div class="comment__content">
									<div class="comment__header">
										<strong>{{ comment.userDisplayName }}</strong>
										<span class="comment__time">{{ formatDate(comment.createdAt) }}</span>
									</div>
									<div v-if="editingCommentId === comment.id" class="comment__edit">
										<textarea v-model="editingCommentText" rows="3" />
										<div class="comment__edit-actions">
											<NcButton type="primary" @click="saveCommentEdit(comment.id)">
												{{ t('tablespro', 'Save') }}
											</NcButton>
											<NcButton @click="cancelCommentEdit">
												{{ t('tablespro', 'Cancel') }}
											</NcButton>
										</div>
									</div>
									<div v-else class="comment__message">{{ comment.message }}</div>
									<div v-if="comment.userId === currentUserId && editingCommentId !== comment.id"
										class="comment__actions">
										<NcButton type="tertiary" @click="startEditComment(comment)">
											<template #icon><PencilIcon :size="16" /></template>
										</NcButton>
										<NcButton type="tertiary" @click="deleteComment(comment.id)">
											<template #icon><DeleteIcon :size="16" /></template>
										</NcButton>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- New comment input -->
					<div v-if="canEdit" class="new-comment">
						<NcAvatar :user="currentUserId" :size="32" />
						<div class="new-comment__input">
							<textarea v-model="newComment"
								:placeholder="t('tablespro', 'Write a comment...')"
								rows="2"
								@keydown.ctrl.enter="addComment" />
							<NcButton type="primary"
								:disabled="!newComment.trim()"
								@click="addComment">
								{{ t('tablespro', 'Send') }}
							</NcButton>
						</div>
					</div>
				</div>

				<!-- Attachments Tab -->
				<div v-else-if="activeTab === 'attachments'" class="tab-attachments">
					<div v-if="loadingAttachments" class="loading">
						<NcLoadingIcon :size="32" />
					</div>
					<div v-else-if="attachments.length === 0" class="empty-state">
						<AttachmentIcon :size="48" />
						<p>{{ t('tablespro', 'No attachments yet') }}</p>
					</div>
					<div v-else class="attachments-list">
						<div v-for="attachment in attachments" :key="attachment.id" class="attachment">
							<FileIcon :size="32" />
							<div class="attachment__info">
								<div class="attachment__name">{{ attachment.fileName || 'File' }}</div>
								<div class="attachment__meta">
									{{ attachment.userDisplayName }} · {{ formatDate(attachment.createdAt) }}
								</div>
							</div>
							<NcButton v-if="attachment.userId === currentUserId || canManage"
								type="tertiary"
								@click="deleteAttachment(attachment.id)">
								<template #icon><DeleteIcon :size="16" /></template>
							</NcButton>
						</div>
					</div>

					<!-- Add attachment button -->
					<div v-if="canEdit" class="add-attachment">
						<NcButton @click="openFilePicker">
							<template #icon><PlusIcon :size="20" /></template>
							{{ t('tablespro', 'Add attachment') }}
						</NcButton>
					</div>
				</div>

				<!-- Activity Tab -->
				<div v-else-if="activeTab === 'activity'" class="tab-activity">
					<div v-if="loadingActivity" class="loading">
						<NcLoadingIcon :size="32" />
					</div>
					<div v-else-if="activities.length === 0" class="empty-state">
						<ActivityIcon :size="48" />
						<p>{{ t('tablespro', 'No activity yet') }}</p>
					</div>
					<div v-else class="activity-list">
						<div v-for="activity in activities" :key="activity.id" class="activity-item">
							<NcAvatar :user="activity.userId"
								:display-name="activity.userDisplayName"
								:size="28" />
							<div class="activity-item__content">
								<span class="activity-item__text">
									<strong>{{ activity.userDisplayName }}</strong>
									{{ getActivityText(activity) }}
								</span>
								<span class="activity-item__time">{{ formatDate(activity.createdAt) }}</span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Footer actions -->
			<div class="card-detail__footer">
				<NcButton v-if="canEdit && hasChanges" type="primary" @click="saveChanges">
					{{ t('tablespro', 'Save changes') }}
				</NcButton>
				<NcButton @click="close">
					{{ t('tablespro', 'Close') }}
				</NcButton>
			</div>
		</div>
	</NcDialog>
</template>

<script>
import { NcDialog, NcButton, NcAvatar, NcProgressBar, NcLoadingIcon } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { showError, showSuccess, getFilePickerBuilder, FilePickerType } from '@nextcloud/dialogs'
import { getCurrentUser } from '@nextcloud/auth'
import { CommentsApi, AttachmentsApi, ActivityApi } from '../../shared/api/cardFeatures.js'
import ColumnFormComponent from '../main/partials/ColumnFormComponent.vue'
import permissionsMixin from '../../shared/components/ncTable/mixins/permissionsMixin.js'
import { mapActions } from 'pinia'
import { useDataStore } from '../../store/data.js'

// Icons
import CalendarIcon from 'vue-material-design-icons/Calendar.vue'
import AccountIcon from 'vue-material-design-icons/Account.vue'
import ProgressIcon from 'vue-material-design-icons/ProgressCheck.vue'
import ChecklistIcon from 'vue-material-design-icons/CheckboxMarkedOutline.vue'
import TextIcon from 'vue-material-design-icons/Text.vue'
import CommentIcon from 'vue-material-design-icons/CommentOutline.vue'
import AttachmentIcon from 'vue-material-design-icons/Paperclip.vue'
import ActivityIcon from 'vue-material-design-icons/LightningBolt.vue'
import FileIcon from 'vue-material-design-icons/File.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import FormatListBulletedIcon from 'vue-material-design-icons/FormatListBulleted.vue'

export default {
	name: 'CardDetailModal',

	components: {
		NcDialog,
		NcButton,
		NcAvatar,
		NcProgressBar,
		NcLoadingIcon,
		ColumnFormComponent,
		CalendarIcon,
		AccountIcon,
		ProgressIcon,
		ChecklistIcon,
		TextIcon,
		CommentIcon,
		AttachmentIcon,
		ActivityIcon,
		FileIcon,
		PencilIcon,
		DeleteIcon,
		PlusIcon,
	},

	mixins: [permissionsMixin],

	props: {
		showModal: {
			type: Boolean,
			default: false,
		},
		row: {
			type: Object,
			default: null,
		},
		columns: {
			type: Array,
			default: () => [],
		},
		isView: {
			type: Boolean,
			default: false,
		},
		element: {
			type: Object,
			default: null,
		},
		titleColumnId: {
			type: Number,
			default: null,
		},
		groupingColumnId: {
			type: Number,
			default: null,
		},
	},

	data() {
		return {
			activeTab: 'details',
			localRow: {},
			originalRow: {},
			comments: [],
			attachments: [],
			activities: [],
			loadingComments: false,
			loadingAttachments: false,
			loadingActivity: false,
			newComment: '',
			editingCommentId: null,
			editingCommentText: '',
			currentUserId: getCurrentUser()?.uid,
		}
	},

	computed: {
		tabs() {
			return [
				{ id: 'details', label: t('tablespro', 'Details'), icon: FormatListBulletedIcon, count: 0 },
				{ id: 'comments', label: t('tablespro', 'Comments'), icon: CommentIcon, count: this.comments.length },
				{ id: 'attachments', label: t('tablespro', 'Attachments'), icon: AttachmentIcon, count: this.attachments.length },
				{ id: 'activity', label: t('tablespro', 'Activity'), icon: ActivityIcon, count: 0 },
			]
		},

		cardTitle() {
			if (this.titleColumnId) {
				const cell = this.row?.data?.find(d => d.columnId === this.titleColumnId)
				if (cell?.value) {
					return String(cell.value).substring(0, 100)
				}
			}
			const textColumn = this.columns.find(c => c.type === 'text-line' || c.type === 'text-rich')
			if (textColumn) {
				const cell = this.row?.data?.find(d => d.columnId === textColumn.id)
				if (cell?.value) {
					return String(cell.value).replace(/<[^>]*>/g, '').substring(0, 100)
				}
			}
			return `#${this.row?.id}`
		},

		nonMetaColumns() {
			return this.columns.filter(col => col.id > 0)
		},

		canEdit() {
			return this.canUpdateData(this.element)
		},

		canManage() {
			return this.canManageTable(this.element)
		},

		hasChanges() {
			return JSON.stringify(this.localRow) !== JSON.stringify(this.originalRow)
		},

		labels() {
			const labels = []
			for (const column of this.columns) {
				if (column.id === this.groupingColumnId) continue
				if (column.id === this.titleColumnId) continue
				if (column.id < 0) continue
				if (column.type === 'selection' || column.type === 'selection-multi') {
					const cell = this.row?.data?.find(d => d.columnId === column.id)
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
			return labels.slice(0, 6)
		},

		dueDate() {
			const dateColumn = this.columns.find(c => c.type === 'datetime' || c.type === 'datetime-date')
			if (!dateColumn) return null
			const cell = this.row?.data?.find(d => d.columnId === dateColumn.id)
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
			return this.dueDate.toLocaleDateString(undefined, {
				weekday: 'short',
				year: 'numeric',
				month: 'short',
				day: 'numeric',
			})
		},

		assignedUsers() {
			const users = []
			const usergroupColumns = this.columns.filter(c => c.type === 'usergroup')
			for (const column of usergroupColumns) {
				const cell = this.row?.data?.find(d => d.columnId === column.id)
				if (cell?.value) {
					const values = Array.isArray(cell.value) ? cell.value : [cell.value]
					for (const val of values) {
						if (val && typeof val === 'object' && val.id) {
							users.push({ id: val.id, displayName: val.displayName || val.id })
						} else if (val && typeof val === 'string') {
							users.push({ id: val, displayName: val })
						}
					}
				}
			}
			return users
		},

		progressValue() {
			const progressColumn = this.columns.find(c => c.type === 'number-progress')
			if (!progressColumn) return null
			const cell = this.row?.data?.find(d => d.columnId === progressColumn.id)
			if (cell?.value === null || cell?.value === undefined) return null
			return Math.min(100, Math.max(0, Number(cell.value)))
		},

		checklistProgress() {
			const richTextColumn = this.columns.find(c => c.type === 'text-rich')
			if (!richTextColumn) return null
			const cell = this.row?.data?.find(d => d.columnId === richTextColumn.id)
			if (!cell?.value) return null
			const content = String(cell.value)
			const checkboxPattern = /\[([ xX])\]/g
			const matches = [...content.matchAll(checkboxPattern)]
			if (matches.length === 0) return null
			const done = matches.filter(m => m[1].toLowerCase() === 'x').length
			return { done, total: matches.length }
		},

		description() {
			const richTextColumn = this.columns.find(c => c.type === 'text-rich')
			if (!richTextColumn) return null
			const cell = this.row?.data?.find(d => d.columnId === richTextColumn.id)
			return cell?.value || null
		},
	},

	watch: {
		showModal: {
			immediate: true,
			handler(val) {
				if (val && this.row) {
					this.loadData()
				}
			},
		},
		row: {
			handler() {
				if (this.showModal && this.row) {
					this.loadData()
				}
			},
		},
	},

	methods: {
		...mapActions(useDataStore, ['updateRow']),
		t,

		loadData() {
			this.initLocalRow()
			this.loadComments()
			this.loadAttachments()
			this.loadActivity()
		},

		initLocalRow() {
			const tmp = {}
			if (this.row?.data) {
				this.row.data.forEach(item => {
					tmp[item.columnId] = item.value
				})
			}
			this.columns.forEach(column => {
				if (!(column.id in tmp)) {
					tmp[column.id] = column.type === 'usergroup' ? [] : null
				}
			})
			this.localRow = { ...tmp }
			this.originalRow = { ...tmp }
		},

		async loadComments() {
			if (!this.row?.id) return
			this.loadingComments = true
			try {
				this.comments = await CommentsApi.getForRow(this.row.id)
			} catch (e) {
				console.error('Failed to load comments:', e)
				this.comments = []
			}
			this.loadingComments = false
		},

		async loadAttachments() {
			if (!this.row?.id) return
			this.loadingAttachments = true
			try {
				this.attachments = await AttachmentsApi.getForRow(this.row.id)
			} catch (e) {
				console.error('Failed to load attachments:', e)
				this.attachments = []
			}
			this.loadingAttachments = false
		},

		async loadActivity() {
			if (!this.row?.id) return
			this.loadingActivity = true
			try {
				this.activities = await ActivityApi.getForRow(this.row.id)
			} catch (e) {
				console.error('Failed to load activity:', e)
				this.activities = []
			}
			this.loadingActivity = false
		},

		async addComment() {
			if (!this.newComment.trim()) return
			try {
				const comment = await CommentsApi.create(
					this.row.id,
					this.row.tableId || this.element?.tableId,
					this.newComment.trim(),
				)
				this.comments.push(comment)
				this.newComment = ''
				showSuccess(t('tablespro', 'Comment added'))
			} catch (e) {
				showError(t('tablespro', 'Failed to add comment'))
				console.error(e)
			}
		},

		startEditComment(comment) {
			this.editingCommentId = comment.id
			this.editingCommentText = comment.message
		},

		cancelCommentEdit() {
			this.editingCommentId = null
			this.editingCommentText = ''
		},

		async saveCommentEdit(commentId) {
			try {
				const updated = await CommentsApi.update(commentId, this.editingCommentText)
				const index = this.comments.findIndex(c => c.id === commentId)
				if (index !== -1) {
					this.comments[index] = updated
				}
				this.cancelCommentEdit()
				showSuccess(t('tablespro', 'Comment updated'))
			} catch (e) {
				showError(t('tablespro', 'Failed to update comment'))
				console.error(e)
			}
		},

		async deleteComment(commentId) {
			if (!confirm(t('tablespro', 'Are you sure you want to delete this comment?'))) return
			try {
				await CommentsApi.delete(commentId)
				this.comments = this.comments.filter(c => c.id !== commentId)
				showSuccess(t('tablespro', 'Comment deleted'))
			} catch (e) {
				showError(t('tablespro', 'Failed to delete comment'))
				console.error(e)
			}
		},

		async openFilePicker() {
			try {
				const picker = getFilePickerBuilder(t('tablespro', 'Select a file to attach'))
					.setMultiSelect(false)
					.setType(FilePickerType.Custom)
					.addButton({
						label: t('tablespro', 'Attach'),
						callback: async (nodes) => {
							if (nodes && nodes.length > 0) {
								const fileInfo = nodes[0]
								await this.addAttachment(fileInfo)
							}
						},
						type: 'primary',
					})
					.build()
				await picker.pick()
			} catch (e) {
				if (e.message !== 'User cancelled' && e.name !== 'AbortError') {
					showError(t('tablespro', 'Failed to open file picker'))
					console.error(e)
				}
			}
		},

		async addAttachment(fileInfo) {
			if (!fileInfo) return

			const tableId = this.row.tableId || this.element?.tableId
			const fileId = fileInfo.fileid || fileInfo.id

			if (!fileId) {
				showError(t('tablespro', 'Could not get file ID'))
				return
			}

			try {
				const attachment = await AttachmentsApi.create(
					this.row.id,
					tableId,
					fileId,
					'file',
				)
				this.attachments.push(attachment)
				showSuccess(t('tablespro', 'File attached successfully'))
			} catch (e) {
				showError(t('tablespro', 'Failed to attach file'))
				console.error(e)
			}
		},

		async deleteAttachment(attachmentId) {
			if (!confirm(t('tablespro', 'Are you sure you want to delete this attachment?'))) return
			try {
				await AttachmentsApi.delete(attachmentId)
				this.attachments = this.attachments.filter(a => a.id !== attachmentId)
				showSuccess(t('tablespro', 'Attachment deleted'))
			} catch (e) {
				showError(t('tablespro', 'Failed to delete attachment'))
				console.error(e)
			}
		},

		onFieldChange(columnId, value) {
			this.localRow[columnId] = value
		},

		async saveChanges() {
			const data = []
			for (const [key, value] of Object.entries(this.localRow)) {
				data.push({ columnId: parseInt(key), value: value ?? '' })
			}

			try {
				await this.updateRow({
					id: this.row.id,
					isView: this.isView,
					elementId: this.element.id,
					data,
				})
				this.originalRow = { ...this.localRow }
				showSuccess(t('tablespro', 'Changes saved'))
			} catch (e) {
				showError(t('tablespro', 'Failed to save changes'))
				console.error(e)
			}
		},

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
			const hex = hexColor.replace('#', '')
			const r = parseInt(hex.substr(0, 2), 16)
			const g = parseInt(hex.substr(2, 2), 16)
			const b = parseInt(hex.substr(4, 2), 16)
			const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255
			return luminance > 0.5 ? '#000000' : '#ffffff'
		},

		getDisplayValue(column) {
			const value = this.localRow[column.id]
			if (value === null || value === undefined) return '-'
			return String(value)
		},

		formatDate(dateString) {
			if (!dateString) return ''
			const date = new Date(dateString)
			const now = new Date()
			const diffMs = now - date
			const diffMins = Math.floor(diffMs / 60000)
			const diffHours = Math.floor(diffMs / 3600000)
			const diffDays = Math.floor(diffMs / 86400000)

			if (diffMins < 1) return t('tablespro', 'Just now')
			if (diffMins < 60) return t('tablespro', '{n} min ago', { n: diffMins })
			if (diffHours < 24) return t('tablespro', '{n} hours ago', { n: diffHours })
			if (diffDays < 7) return t('tablespro', '{n} days ago', { n: diffDays })
			return date.toLocaleDateString()
		},

		getActivityText(activity) {
			const actionTexts = {
				create: t('tablespro', 'created this card'),
				update: t('tablespro', 'updated this card'),
				delete: t('tablespro', 'deleted this card'),
				comment: t('tablespro', 'added a comment'),
				attachment: t('tablespro', 'added an attachment'),
				move: t('tablespro', 'moved this card'),
			}
			return actionTexts[activity.action] || activity.action
		},

		close() {
			if (this.hasChanges) {
				if (!confirm(t('tablespro', 'You have unsaved changes. Discard them?'))) {
					return
				}
			}
			this.$emit('close')
		},
	},
}
</script>

<style lang="scss" scoped>
.card-detail-modal :deep(.modal-container) {
	min-height: 80vh;
}

.card-detail {
	display: flex;
	flex-direction: column;
	height: 100%;
	min-height: 500px;
}

.card-detail__labels {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin-bottom: 16px;
}

.card-detail__label {
	display: inline-block;
	padding: 4px 12px;
	font-size: 12px;
	font-weight: 500;
	border-radius: var(--border-radius-pill);
	background-color: var(--color-primary-element-light);
	color: var(--color-primary-element);
}

.card-detail__tabs {
	display: flex;
	gap: 8px;
	margin-bottom: 20px;
	padding-bottom: 16px;
	border-bottom: 1px solid var(--color-border);
	flex-wrap: wrap;
}

.tab-count {
	margin-left: 4px;
	padding: 2px 6px;
	font-size: 11px;
	background-color: var(--color-background-dark);
	border-radius: var(--border-radius-pill);
}

.card-detail__content {
	flex: 1;
	overflow-y: auto;
	padding-right: 8px;
}

// Details tab
.tab-details {
	display: flex;
	flex-direction: column;
	gap: 16px;
}

.detail-item {
	display: flex;
	align-items: flex-start;
	gap: 12px;

	&--full {
		flex-direction: column;
	}
}

.detail-item__label {
	display: flex;
	align-items: center;
	gap: 8px;
	min-width: 120px;
	color: var(--color-text-maxcontrast);
	font-weight: 500;
}

.detail-item__value {
	flex: 1;

	&.overdue {
		color: var(--color-error);
		font-weight: 600;
	}

	&.due-soon {
		color: var(--color-warning);
		font-weight: 600;
	}
}

.assigned-users {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.assigned-user {
	display: flex;
	align-items: center;
	gap: 8px;
}

.progress-text {
	margin-left: 8px;
	font-weight: 500;
}

.description {
	padding: 12px;
	background-color: var(--color-background-dark);
	border-radius: var(--border-radius-large);
	max-height: 200px;
	overflow-y: auto;
}

.detail-fields {
	margin-top: 24px;
	padding-top: 24px;
	border-top: 1px solid var(--color-border);

	h3 {
		margin: 0 0 16px;
		font-size: 14px;
		font-weight: 600;
	}
}

.field-item {
	margin-bottom: 16px;
}

.field-item__label {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
	margin-bottom: 4px;
}

// Comments tab
.tab-comments {
	display: flex;
	flex-direction: column;
	height: 100%;
}

.comments-list {
	flex: 1;
	overflow-y: auto;
	margin-bottom: 16px;
}

.comment {
	display: flex;
	gap: 12px;
	padding: 12px;
	border-radius: var(--border-radius-large);

	&:hover {
		background-color: var(--color-background-hover);

		.comment__actions {
			opacity: 1;
		}
	}
}

.comment__content {
	flex: 1;
}

.comment__header {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 4px;

	strong {
		font-size: 14px;
	}
}

.comment__time {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

.comment__message {
	font-size: 14px;
	line-height: 1.5;
	white-space: pre-wrap;
}

.comment__actions {
	display: flex;
	gap: 4px;
	opacity: 0;
	transition: opacity 0.15s ease;
}

.comment__edit {
	margin-top: 8px;

	textarea {
		width: 100%;
		padding: 8px;
		border: 1px solid var(--color-border);
		border-radius: var(--border-radius);
		resize: vertical;
	}
}

.comment__edit-actions {
	display: flex;
	gap: 8px;
	margin-top: 8px;
}

.new-comment {
	display: flex;
	gap: 12px;
	padding-top: 16px;
	border-top: 1px solid var(--color-border);
}

.new-comment__input {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 8px;

	textarea {
		width: 100%;
		padding: 8px;
		border: 1px solid var(--color-border);
		border-radius: var(--border-radius);
		resize: vertical;
	}

	button {
		align-self: flex-end;
	}
}

// Attachments tab
.attachments-list {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.attachment {
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 12px;
	background-color: var(--color-background-dark);
	border-radius: var(--border-radius-large);
}

.attachment__info {
	flex: 1;
}

.attachment__name {
	font-weight: 500;
}

.attachment__meta {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

.add-attachment {
	margin-top: 16px;
}

// Activity tab
.activity-list {
	display: flex;
	flex-direction: column;
	gap: 12px;
}

.activity-item {
	display: flex;
	gap: 12px;
	padding: 8px;
}

.activity-item__content {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 2px;
}

.activity-item__text {
	font-size: 14px;
}

.activity-item__time {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

// Footer
.card-detail__footer {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	margin-top: 20px;
	padding-top: 16px;
	border-top: 1px solid var(--color-border);
}

// Loading & empty states
.loading {
	display: flex;
	justify-content: center;
	padding: 40px;
}

.empty-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 40px;
	color: var(--color-text-maxcontrast);

	p {
		margin-top: 12px;
	}
}
</style>
