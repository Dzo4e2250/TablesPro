<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TablesPro\Controller;

use Closure;
use InvalidArgumentException;
use OCA\TablesPro\Errors\BadRequestError;
use OCA\TablesPro\Errors\InternalError;
use OCA\TablesPro\Errors\NotFoundError;
use OCA\TablesPro\Errors\PermissionError;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

trait Errors {

	protected function handleError(Closure $callback): DataResponse {
		try {
			return new DataResponse($callback());
		} catch (BadRequestError $e) {
			$this->logger->warning('An bad request was encountered: ' . $e->getMessage(), ['exception' => $e]);
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		} catch (PermissionError $e) {
			$this->logger->warning('A permission error occurred: ' . $e->getMessage(), ['exception' => $e]);
			$message = ['message' => $e->getMessage()];
			return new DataResponse($message, Http::STATUS_FORBIDDEN);
		} catch (NotFoundError $e) {
			$this->logger->warning('A not found error occurred: ' . $e->getMessage(), ['exception' => $e]);
			$message = ['message' => $e->getMessage()];
			return new DataResponse($message, Http::STATUS_NOT_FOUND);
		} catch (InvalidArgumentException $e) {
			$this->logger->warning('An invalid request occurred: ' . $e->getMessage(), ['exception' => $e]);
			$message = ['message' => $e->getMessage()];
			return new DataResponse($message, Http::STATUS_BAD_REQUEST);
		} catch (InternalError|\Exception $e) {
			$this->logger->error('An internal error or exception occurred: ' . $e->getMessage(), ['exception' => $e]);
			$message = ['message' => $e->getMessage()];
			return new DataResponse($message, Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
}
