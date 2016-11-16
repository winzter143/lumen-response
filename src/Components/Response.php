<?php
namespace F3\Components;

use Illuminate\Http\Response as HttpResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class Response extends HttpResponse 
{
    /**
     * List of supported status codes.
     */
    private static $status = [
        // 200
        self::HTTP_OK,
        // 400
        self::HTTP_BAD_REQUEST,
        // 401
        self::HTTP_UNAUTHORIZED,
        // 413
        self::HTTP_REQUEST_ENTITY_TOO_LARGE,
        // 422
        self::HTTP_UNPROCESSABLE_ENTITY,
        // 404
        self::HTTP_NOT_FOUND,
        // 429
        self::HTTP_TOO_MANY_REQUESTS,
        // 500
        self::HTTP_INTERNAL_SERVER_ERROR
    ];

    /**
     * List of error description.
     */
    private static $description = [
        self::HTTP_BAD_REQUEST => "We didn't understand your request. Please make sure that you're sending a valid JSON payload in the request body.",
        self::HTTP_UNAUTHORIZED => "The token is not valid or may have already expired.",
        self::HTTP_REQUEST_ENTITY_TOO_LARGE => "You have exceeded the maximum allowable upload size of 2MB.",
        self::HTTP_UNPROCESSABLE_ENTITY => "Some of the parameters failed validation.",
        self::HTTP_NOT_FOUND => "The entity that you're looking for does not exist.",
        self::HTTP_TOO_MANY_REQUESTS => "Your account has been temporarily suspended because you have exceeded the allowable number of requests.",
        self::HTTP_INTERNAL_SERVER_ERROR => "Something went wrong. We're looking into it.",
    ];

    /**
     * Constructor
     */
    public function __construct($content = '', $status = self::HTTP_OK, $headers = [])
    {
        // Send back unknown errors as HTTP 500 for now.
        if (!in_array($status, self::$status)) {
            $status = self::HTTP_INTERNAL_SERVER_ERROR;

            // Update the content status.
            if (isset($content['status'])) {
                $content['status'] = $status;
            }

            // Update the content message.
            if (isset($content['message'])) {
                $content['message'] = self::$statusTexts[$status];
            }
        }

        // Hide the error description if the status code is 500.
        if ($status ===  self::HTTP_INTERNAL_SERVER_ERROR && isset($content['description'])) {
            $content['description'] = self::$description[self::HTTP_INTERNAL_SERVER_ERROR];
        }

        // Create the response object.
        return parent::__construct($content, $status, $headers);
    }

    /**
     * Builds the error response.
     */
    public static function error($status, $description = null, Validator $validator = null)
    {
        // Build the response data.
        $content = [
            'status' => $status,
            'message' => array_get(self::$statusTexts, $status, 'Unknown Error'),
            'description' => ($description) ?: array_get(self::$description, $status, self::$description[self::HTTP_INTERNAL_SERVER_ERROR])
        ];

        // Add the validator errors if available.
        if ($validator) {
            $content['parameters'] = $validator->messages();
        }

        // Send it back.
        return new self($content, $status);
    }

    /**
     * Sends back the proper status code and message based on the exception.
     * @param \Exception $e
     */
    public static function exception(\Exception $e)
    {
        if ($e instanceof ValidationException) {
            // This is a validation error. Send back a 422.
            return self::error(self::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage(), $e->validator);
        } else {
            return self::error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Generates the pagination header.
     */
    public function withPagingHeaders()
    {
        // Change orders.api.lbcx.ph to api.lbcx.ph.
        $prefixes = ['orders.', 'finance.', 'f3.'];
        $replacements = [''];

        // Update the links in the response body.
        $this->content = json_decode($this->content);

        if (isset($this->content->next_page_url)) {
            $this->content->next_page_url = str_replace($prefixes, $replacements, $this->content->next_page_url);
        }

        if (isset($this->content->prev_page_url)) {
            $this->content->prev_page_url = str_replace($prefixes, $replacements, $this->content->prev_page_url);
        }

        $this->content = json_encode($this->content);

        // Check if the original content is a paginator.
        if (!($this->original instanceof LengthAwarePaginator)) {
            return $this;
        }

        // Build the links.
        $links = [];
        $prev = str_replace($prefixes, $replacements, $this->original->previousPageUrl());
        $next = str_replace($prefixes, $replacements, $this->original->nextPageUrl());
        
        // Previous page link.
        if ($prev) {
            $links[] = '<' . $prev . '>; rel="previous"';
        }

        // Next page link.
        if ($next) {
            $links[] = '<' . $next . '>; rel="next"';
        }

        // Build the headers.
        $this->header('X-Pagination-Total-Count', $this->original->total());
        $this->header('X-Pagination-Page-Count', $this->original->lastPage());
        $this->header('X-Pagination-Current-Page', $this->original->currentPage());
        $this->header('X-Pagination-Per-Page', $this->original->perPage());
        $this->header('Link', ($links) ? implode(', ', $links) : null);

        return $this;
    }
}
