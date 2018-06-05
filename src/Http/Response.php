<?php

namespace Xabi\Http;

use Symfony\Component\HttpFoundation\Response as BaseResponse;

class Response extends BaseResponse {
	use ResponseTrait;

	/**
	 * Set the content on the response.
	 *
	 * @param  mixed  $content
	 * @return $this
	 */
	public function setContent($content) {
		$this->original = $content;

		// If the content is "JSONable" we will set the appropriate header and convert
		// the content to JSON. This is useful when returning something like models
		// from routes that will be automatically transformed to their JSON form.
		if ($this->shouldBeJson($content)) {
			$this->header('Content-Type', 'application/json');

			$content = $this->morphToJson($content);
		}

		return parent::setContent($content);
	}

	/**
	 * Determine if the given content should be turned into JSON.
	 *
	 * @param  mixed  $content
	 * @return bool
	 */
	protected function shouldBeJson($content) {
		return is_array($content);
	}

	/**
	 * Morph the given content into JSON.
	 *
	 * @param  mixed   $content
	 * @return string
	 */
	protected function morphToJson($content) {
		if ($content instanceof Jsonable) {
			return $content->toJson();
		}

		return json_encode($content);
	}
}
