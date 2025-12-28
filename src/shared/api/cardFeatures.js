/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Comments API
 */
export const CommentsApi = {
	/**
	 * Get all comments for a row
	 * @param {number} rowId
	 * @returns {Promise<Array>}
	 */
	async getForRow(rowId) {
		const res = await axios.get(generateUrl(`/apps/tablespro/row/${rowId}/comments`))
		return res.data
	},

	/**
	 * Get a single comment
	 * @param {number} commentId
	 * @returns {Promise<Object>}
	 */
	async get(commentId) {
		const res = await axios.get(generateUrl(`/apps/tablespro/comment/${commentId}`))
		return res.data
	},

	/**
	 * Create a new comment
	 * @param {number} rowId
	 * @param {number} tableId
	 * @param {string} message
	 * @param {number|null} replyTo
	 * @returns {Promise<Object>}
	 */
	async create(rowId, tableId, message, replyTo = null) {
		const res = await axios.post(generateUrl('/apps/tablespro/comment'), {
			rowId,
			tableId,
			message,
			replyTo,
		})
		return res.data
	},

	/**
	 * Update a comment
	 * @param {number} commentId
	 * @param {string} message
	 * @returns {Promise<Object>}
	 */
	async update(commentId, message) {
		const res = await axios.put(generateUrl(`/apps/tablespro/comment/${commentId}`), {
			message,
		})
		return res.data
	},

	/**
	 * Delete a comment
	 * @param {number} commentId
	 * @returns {Promise<void>}
	 */
	async delete(commentId) {
		await axios.delete(generateUrl(`/apps/tablespro/comment/${commentId}`))
	},

	/**
	 * Get comment count for a row
	 * @param {number} rowId
	 * @returns {Promise<number>}
	 */
	async countForRow(rowId) {
		const res = await axios.get(generateUrl(`/apps/tablespro/row/${rowId}/comments/count`))
		return res.data.count
	},
}

/**
 * Attachments API
 */
export const AttachmentsApi = {
	/**
	 * Get all attachments for a row
	 * @param {number} rowId
	 * @returns {Promise<Array>}
	 */
	async getForRow(rowId) {
		const res = await axios.get(generateUrl(`/apps/tablespro/row/${rowId}/attachments`))
		return res.data
	},

	/**
	 * Get a single attachment
	 * @param {number} attachmentId
	 * @returns {Promise<Object>}
	 */
	async get(attachmentId) {
		const res = await axios.get(generateUrl(`/apps/tablespro/attachment/${attachmentId}`))
		return res.data
	},

	/**
	 * Create a new attachment
	 * @param {number} rowId
	 * @param {number} tableId
	 * @param {number} fileId
	 * @param {string} type
	 * @returns {Promise<Object>}
	 */
	async create(rowId, tableId, fileId, type = 'file') {
		const res = await axios.post(generateUrl('/apps/tablespro/attachment'), {
			rowId,
			tableId,
			fileId,
			type,
		})
		return res.data
	},

	/**
	 * Delete an attachment
	 * @param {number} attachmentId
	 * @returns {Promise<void>}
	 */
	async delete(attachmentId) {
		await axios.delete(generateUrl(`/apps/tablespro/attachment/${attachmentId}`))
	},

	/**
	 * Get attachment count for a row
	 * @param {number} rowId
	 * @returns {Promise<number>}
	 */
	async countForRow(rowId) {
		const res = await axios.get(generateUrl(`/apps/tablespro/row/${rowId}/attachments/count`))
		return res.data.count
	},
}

/**
 * Activity API
 */
export const ActivityApi = {
	/**
	 * Get activity for a row
	 * @param {number} rowId
	 * @param {number} limit
	 * @param {number} offset
	 * @returns {Promise<Array>}
	 */
	async getForRow(rowId, limit = 50, offset = 0) {
		const res = await axios.get(generateUrl(`/apps/tablespro/row/${rowId}/activity`), {
			params: { limit, offset },
		})
		return res.data
	},

	/**
	 * Get activity for a table
	 * @param {number} tableId
	 * @param {number} limit
	 * @param {number} offset
	 * @returns {Promise<Array>}
	 */
	async getForTable(tableId, limit = 50, offset = 0) {
		const res = await axios.get(generateUrl(`/apps/tablespro/table/${tableId}/activity`), {
			params: { limit, offset },
		})
		return res.data
	},
}
