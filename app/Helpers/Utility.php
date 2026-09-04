<?php

namespace App\Helpers;

use App\Http\Requests\SearchRequest;
use App\Models\Central\Tenant;
use App\Models\Configurations\ConfigHead;
use App\Models\Masters\Unit;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

use function Laravel\Prompts\select;

/**
 * This helper handle the utilities used in multiple places in the project
 */
class Utility
{
    /**
     * Receives a collection, field name and a word,
     * and finds the next item available word if it already exists in DB
     */
    public static function findNextAvailableWord($items, $field, $word, string $separator = '_'): string
    {
        $count = 1;
        $notFound = true;
        while ($notFound) {
            if (!$items->firstWhere($field, $word . $separator . $count)) {
                $word = $word . $separator . $count;
                $notFound = false;
            }
            $count++;
        }

        return $word;
    }

    /**
     * Clean white spaces and special characters from a word.
     */
    public static function sanitizeWord($word, $separator): string
    {
        return preg_replace('/[^A-Za-z0-9_]/', $separator, str_replace([' ', '&'], ['-', ''], $word));
    }

    /**
     * Generates a unique word verifying it does not exist in the DB in the selected table
     *
     * @param  null  $id
     */
    public static function generateUniqueWord(
        $table,
        $field,
        $word,
        string $separator = '',
        string $idFieldName = 'id',
        $id = null
    ): string {
        $word = Str::lower(self::sanitizeWord($word, $separator));
        $exactQuery = DB::table($table)->select([$field])
            ->whereRaw('lower(' . $field . ") = '" . $word . "'");
        if ($id) {
            $exactQuery->where($idFieldName, '<>', $id);
        }
        $exactResults = $exactQuery->get();
        if ($exactResults->count() > 0) {
            $query = DB::table($table)->select([$field])
                ->whereRaw('lower(' . $field . ") like('" . $word . "%')");
            if ($id) {
                $query->where($idFieldName, '<>', $id);
            }
            $results = $query->get();
            if ($results->count() > 0) {
                $word = self::findNextAvailableWord($results, $field, $word, $separator);
            }
        }

        return $word;
    }

    /**
     * Receives a string and converts it to a KEY, it takes every first 2 letters from each
     * word in the string and merge them
     */
    public static function generateKeyFromString(string $inputString, int $length = 2): string
    {
        if (empty($inputString)) {
            return $inputString;
        }

        // Remove everything except letters from the input string
        $inputString = preg_replace('/[^A-Za-z ]/', '', $inputString);

        // Split the input string into an array of words
        $words = explode(' ', $inputString);

        // Initialize an array to store the first two letters from each word
        $firstTwoLetters = [];

        // Iterate through each word and extract the first two letters
        foreach ($words as $word) {
            // Use substr to get the first two letters (or less if the word is shorter)
            $firstTwo = substr($word, 0, $length);

            // Add the first two letters to the result array
            $firstTwoLetters[] = $firstTwo;
        }

        // Join the extracted letters back into a string
        return implode(' ', $firstTwoLetters);
    }

    /**
     * Receives a SearchRequest request and a query, sets the corresponding order_by and
     * returns a paginated or not paginated result
     *
     * @return mixed $result
     */
    public static function getSearchRequestQueryResults(
        Request $request,
        $query,
        int $itemsPerPage = 0,
        string $alias = ''
    ) {
        if ($request->has('order_by')) {
            $requestSort = $request->sort ?: 'ASC';
            $orderClause = !empty($alias) ? $alias . '.' . $request->order_by : $request->order_by;
            if (!empty($alias)) {
                $tableName = $alias;
            } else {
                $tableName = $query->getModel()->getTable();
            }
            // check field exists in table
            $fieldExists = DB::select('SELECT column_name FROM information_schema.columns WHERE table_name = ? AND column_name = ?', [$tableName, $request->order_by]);
            if ($fieldExists) {
                $dataType = DB::select('SELECT data_type FROM information_schema.columns WHERE table_name = ? AND column_name = ?', [$tableName, $request->order_by]);
            } else {
                // remove alias from order by clause
                $orderClause = str_replace($alias . '.', '', $orderClause);
            }
            $fieldDataType = '';
            if (!empty($dataType)) {
                $fieldDataType = isset($dataType[0]->data_type) ? $dataType[0]->data_type : (isset($dataType[0]->DATA_TYPE) ? $dataType[0]->DATA_TYPE : '');
                if ($fieldDataType && ($fieldDataType !== "datetime") && ($fieldDataType !== "bigint") && ($fieldDataType !== "int")) {
                    $regex = $requestSort == "ASC" ? "a-zA-Z" : "0-9";
                    $query->orderByRaw(" CASE WHEN LOWER($orderClause) REGEXP '^[" . $regex . "]' THEN 0 ELSE 1 END, LOWER($orderClause) " . $requestSort);
                } else {
                    $query->orderByRaw("$orderClause $requestSort");
                }
            } else {
                $query->orderByRaw("LOWER($orderClause)" . $requestSort);
            }
        } else {
            // Default ordering by id in descending order (newest first)
            $query->orderBy('id', 'desc');
        }

        if ($request->has('paged') && $request->paged) {
            $itemsPerPage = $itemsPerPage > 0 ? $itemsPerPage : ($request->itemsPerPage ?? config('common.searchPagingLength'));
            if (intval($itemsPerPage) > 0) {
                Log::info('itemsPerPage: ' . $itemsPerPage);
                Log::info('paged: ' . $request->paged);
                $result = $query->paginate($itemsPerPage, ['*'], 'page', $request->paged);
            } else {
                // $result = $query->get();
                $result = $query->paginate($query->count(), ['*'], 'page', $request->paged);
                $result->appends($request->query());
            }
        } else {
            if ($request->has('limit')) {
                $result = $query->limit($request->limit);
            }
            $result = $query->get();
        }

        // Set appends
        if ($request->has('appends') && is_array($request->appends)) {
            if ($result instanceof LengthAwarePaginator) {
                $result->getCollection()->each->setAppends($request->appends);
            } else {
                $result->each->setAppends($request->appends);
            }
        }

        return $result;
    }

    /**
     * Validates the logged user is the owner
     *
     * @throws ValidationException
     */
    public static function validateOwnership($loggedUserId, $authorId): void
    {
        if ($loggedUserId !== $authorId) {
            throw ValidationException::withMessages(
                ['message' => config('genericMessages.error.UNAUTHORIZED')]
            );
        }
    }

    /**
     * Receives a model and search array and returns a query with the corresponding search
     *
     * @param  Model  $modelParams
     * this method can be used for general search, it can be used for any model that has a searchable
     *
     * @return Model $model
     *
     * Supports:
     * - 'q' parameter: General search across all accessable_columns defined in the model
     * - 'search' parameter: Specific field searches with operators (like, ilike, in, between, =, json, boolean, etc.)
     *
     * Currently supported operators: like, ilike, in, between, =, json, boolean, none
     *
     * @throws ValidationException
     */
    public static function prepareSearchQuery($query, $request, $model, string $alias = '')
    {
        if ($request->has('with')) {
            $query->with($request->with);
        }

        if ($request->has('select')) {
            $query->select($request->select);
        }

        // General search query using 'q' parameter across searchable columns
        if ($request->has('q') && !empty($request->q)) {
            $searchTerm = strtolower(trim($request->q));
            $searchableColumns = is_array($model::$searchable) ? $model::$searchable : [];

            if (!empty($searchableColumns)) {
                $query->where(function ($query) use ($searchTerm, $searchableColumns, $alias) {
                    foreach ($searchableColumns as $key => $value) {
                        // Mixed array support:
                        // Numeric key => value is the column name (legacy plain list, treat as 'like')
                        // String key  => key is column name, value is operator (only search if like/ilike)
                        if (is_int($key)) {
                            $column = $value;
                            $operator = 'like';
                        } else {
                            $column = $key;
                            $operator = $value;
                        }

                        // Only include columns with a LIKE-style operator in general search
                        if (!in_array($operator, ['like', 'ilike'])) {
                            continue;
                        }

                        // Check if column is a relationship (contains dot)
                        if (str_contains($column, '.')) {
                            [$relation, $relationColumn] = explode('.', $column, 2);

                            // Use whereHas for relationship columns
                            $query->orWhereHas($relation, function ($relQuery) use ($relationColumn, $searchTerm) {
                                $relQuery->whereRaw('lower(' . $relationColumn . ') like ?', ['%' . $searchTerm . '%']);
                            });
                        } else {
                            // Direct column search
                            $columnName = !empty($alias) ? $alias . '.' . $column : $column;
                            $query->orWhereRaw('lower(' . $columnName . ') like ?', ['%' . $searchTerm . '%']);
                        }
                    }
                });
            }
        }
        // Check for Request class
        if ($request instanceof Request) {
            $search = $request->search;
        } else {
            $search = $request->get('search');
        }

        if ($request->has('search') && !empty($search)) {
            $query->where(function ($query) use ($search, $model, $alias) {
                foreach ($search as $key => $value) {
                    // Skip empty string / null values (e.g. "All Status" filter = '' means no filter)
                    if ($value === '' || $value === null) {
                        continue;
                    }

                    $operator = '';
                    if (is_array($model::$searchable)) {
                        $operator = $model::$searchable[$key] ?? '=';

                        if (!empty($alias) && !str_contains($key, '.')) {
                            $key = $alias . '.' . $key;
                        }
                    }
                    if (is_array($value)) {
                        // Case: multiple not-equal values like ["!=ADMIN", "!=SUPER ADMIN"]
                        $notValues = array_filter($value, fn($v) => is_string($v) && str_starts_with($v, '!='));

                        if (count($notValues) === count($value)) {
                            // All values start with '!=', treat as NOT IN
                            $operator = 'not in';
                            $value = array_map(fn($v) => ltrim($v, '!='), $value);
                        }
                    } elseif (is_string($value)) {
                        // Single string case
                        if (str_starts_with($value, '!=')) {
                            $operator = '!=';
                            $value = ltrim($value, '!=');
                        } elseif (str_starts_with($value, '<>')) {
                            $operator = '<>';
                            $value = ltrim($value, '<>');
                        }
                    }
                    if (str_contains($key, '.')) {
                        Log::info('key info', [
                            'key' => $key,
                            'type' => gettype($key),
                            'raw' => $key,
                            'length' => strlen($key),
                        ]);
                        [$relation, $column] = explode('.', $key, 2);

                        $query->whereHas($relation, function ($relQuery) use ($relation, $column, $value, $operator) {

                            switch ($operator) {
                                case 'like':
                                    $relQuery->whereRaw('LOWER(' . $column . ') LIKE ?', ['%' . strtolower(trim($value)) . '%']);
                                    break;

                                case 'ilike':
                                    $relQuery->whereRaw('LOWER(' . $column . ') ILIKE ?', ['%' . strtolower(trim($value)) . '%']);
                                    break;

                                case 'in':
                                    // Support comma-separated string for 'in' operator
                                    if (is_string($value) && str_contains($value, ',')) {
                                        $values = array_map('trim', explode(',', $value));
                                    } else {
                                        $values = is_array($value) ? $value : [$value];
                                    }
                                    $relQuery->whereIn($column, $values);
                                    break;
                                case '!=':
                                case '<>':
                                    $relQuery->where($column, '!=', $value);
                                    break;
                                case 'not in':
                                    $relQuery->whereNotIn($column, $value);
                                    break;

                                case 'between':
                                    $values = is_array($value)
                                        ? (count($value) === 2 ? $value : [$value[0], $value[0]])
                                        : [$value, $value];

                                    if (strpos($values[1], ' ') === false) {
                                        $values[1] .= ' 23:59:59';
                                    }

                                    $relQuery->whereBetween($column, $values);
                                    break;

                                case '=':
                                    if ($value === 'null') {
                                        $relQuery->whereNull($column);
                                    } elseif ($value === 'not-null') {
                                        $relQuery->whereNotNull($column);
                                    } else {
                                        $relQuery->where($column, $value);
                                    }
                                    break;

                                case 'not-null':
                                    $relQuery->whereNotNull($column);
                                    break;

                                case 'boolean':
                                    $relQuery->where($column, boolval($value));
                                    break;

                                case 'json':
                                    if (!is_array($value)) {
                                        throw ValidationException::withMessages([
                                            'search' => $relation . '.' . $column . ' must be an array',
                                        ]);
                                    }
                                    $relQuery->where(function ($q) use ($column, $value) {
                                        foreach ($value as $property => $item) {
                                            $q->whereRaw('LOWER(' . $column . '->>\'' . $property . '\') ILIKE ?', ['%' . strtolower(trim($item)) . '%']);
                                        }
                                    });
                                    break;

                                case 'none':
                                    // Do nothing
                                    break;

                                default:
                                    throw ValidationException::withMessages([
                                        'search' => 'ERROR::' . __CLASS__ . '::' . __FUNCTION__ . '::relationship validation not implemented for: ' . $operator,
                                    ]);
                            }
                        });

                        continue;
                    }
                    switch ($operator) {
                        case 'like':
                            $query->whereRaw('lower(' . $key . ') like ?', ['%' . strtolower(trim($value)) . '%']);
                            break;
                        case 'ilike':
                            $query->whereRaw('lower(' . $key . ') ilike ?', ['%' . strtolower(trim($value)) . '%']);
                            break;
                        case 'in':
                            if (!is_array($value) && ($value == 'null' || $value == 'not-null')) {
                                $query->where(function ($query) use ($key, $value) {
                                    if ($value === 'null') {
                                        $query->whereNull($key);
                                    } elseif ($value === 'not-null') {
                                        $query->whereNotNull($key);
                                    } else {
                                        $query->where($key, $value);
                                    }
                                });
                                break;
                            }
                            // Support comma-separated string for 'in' operator
                            if (is_string($value) && str_contains($value, ',')) {
                                $value = array_map('trim', explode(',', $value));
                            } else {
                                $value = is_array($value) ? $value : [$value];
                            }
                            $query->whereIn($key, $value);
                            break;
                        case 'between':
                            $value = is_array($value) ?
                                (count($value) === 2 ? $value : [$value[0], $value[0]]) :
                                [$value, $value];
                            // IF NO TIME IS PROVIDED, ADD 23:59:59 TO THE END DATE
                            if (strpos($value[1], ' ') === false) {
                                $value[1] = $value[1] . ' 23:59:59';
                            }
                            $query->whereBetween($key, $value);
                            break;
                        case '=':
                            if ($value === 'null') {
                                $query->whereNull($key);
                            } elseif ($value === 'not-null') {
                                $query->whereNotNull($key);
                            } else {
                                $query->where($key, $value);
                            }
                            break;
                        case 'not-null':
                            $query->whereNotNull($key);
                            break;
                        case 'json':
                            if (!is_array($value)) {
                                throw ValidationException::withMessages([
                                    'search' => $key . ' must be an array',
                                ]);
                            }
                            $query->where(function ($query) use ($key, $value) {
                                foreach ($value as $property => $item) {
                                    $query->whereRaw('lower(' . $key . '->>\'' . $property . '\') ilike ?', ['%' . strtolower(trim($item)) . '%']);
                                }
                            });
                            break;
                        case 'boolean':
                            $query->where($key, boolval($value));
                            break;
                        case 'none':
                            // Do nothing
                            break;
                        default:
                            throw ValidationException::withMessages([
                                'search' => 'ERROR::' . __CLASS__ . '::' . __FUNCTION__ . '::validation not implemented for: ' . $operator,
                            ]);
                    }
                }
            });
        }

        return $query;
    }

    /**
     * Receives two dates and returns the days between them
     */
    public static function getDaysBetweenDates(DateTime $startDate, DateTime $endDate): string
    {
        $interval = $startDate->diff($endDate);

        return $interval->format('%a');
    }

    /**
     * Receives a datetime and returns date time in the logged user's timezone
     */
    public static function convertToLoggedUserTimezone($datetime, $format = 'Y-m-d H:i:s'): string
    {
        // india's timezone
        $timezone = 'Asia/Kolkata';
        $date = Carbon::parse($datetime)->timezone($timezone);
        return $date->format($format);
    }


    public static function getRandomColor()
    {
        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }

    public static function getSecondsToHumanReadable($seconds)
    {
        if (!$seconds) {
            return '';
        }
        $seconds = (int) $seconds;
        $duration = CarbonInterval::seconds($seconds)->cascade()->forHumans();
        // $time = now()->subSeconds($seconds)->diffForHumans(now(), CarbonInterface::DIFF_ABSOLUTE);
        // logger($time);
        return $duration;
    }
    public static function deleteErrorMessage($from, $to): string
    {
        return "Cannot delete the {$from} because it has related {$to}.";
    }

    public static function replaceVariablesWithValues($configuration, $variables, $depth = 0): array
    {
        if ($depth > 8) { // Laravel/Symfony default limit is 9
            return ['error' => 'Too deeply nested'];
        }

        $data = [];

        foreach ($configuration as $key => $template) {
            if (gettype($template) === 'array') {
                $data[$key] = self::replaceVariablesWithValues($template, $variables);
            } else if (gettype($template) === 'string') {
                if (self::isHtmlString($template)) {
                    $data[$key] = self::processHtmlString($template, $variables);
                } else {
                    foreach ($variables as $variable_key => $value) {
                        $placeholder = '{{' . $variable_key . '}}';
                        $template = str_replace($placeholder, $value, $template);
                    }
                    $data[$key] = $template;
                }
            } else if (gettype($template) === 'object') {
                foreach ($template as $prop => $content) {
                    if (is_string($content)) {
                        if (self::isHtmlString($content)) {
                            $template->$prop = self::processHtmlString($content, $variables);
                        } else {
                            foreach ($variables as $variable_key => $value) {
                                $placeholder = '{{' . $variable_key . '}}';
                                $content = str_replace($placeholder, $value, $content);
                            }
                            $template->$prop = $content;
                        }
                    } else if (is_array($content)) {
                        $template->$prop = self::replaceVariablesWithValues($content, $variables, $depth + 1);
                    }
                }
                $data[$key] = $template;
            } else {
                $data[$key] = $template;
            }
        }

        return $data;
    }

    private static function isHtmlString(string $s): bool
    {
        // escaped tokens or raw tag start
        if (strpos($s, '&lt;') !== false || strpos($s, '&gt;') !== false || strpos($s, '&amp;') !== false) {
            return true;
        }
        if (preg_match('/<\s*[a-zA-Z!]/', $s)) {
            return true;
        }
        return false;
    }

    public static function processHtmlString(string $htmlString, array $variables): string
    {
        $htmlString = preg_replace('/^\s*<pre><code[^>]*>/i', '', $htmlString);
        $htmlString = preg_replace('/<\/code>\s*<\/pre>\s*$/i', '', $htmlString);

        // decode entities so replacements inside escaped HTML will match
        $htmlString = html_entity_decode($htmlString, ENT_QUOTES | ENT_HTML5);

        // replace placeholders
        foreach ($variables as $variable_key => $replacement) {
            if (is_array($replacement) || is_object($replacement)) {
                $replacement = json_encode($replacement);
            } else {
                $replacement = (string) $replacement;
            }

            $pattern = '/\{\{\s*' . preg_quote((string) $variable_key, '/') . '\s*\}\}/';
            $htmlString = preg_replace($pattern, $replacement, $htmlString);
        }
        $lines = explode("\n", $htmlString);
        $lines = array_map(fn($line) => ltrim($line), $lines);
        $htmlString = implode("\n", $lines);
        $htmlString = trim($htmlString);
        return $htmlString;
    }

    public static function formatMobileNumber($number, $countryCode = '91')
    {
        // if prefix by 91 and count is 12 then remove prefix
        if ((Str::startsWith($number, '91') || Str::startsWith($number, '+91')) && Str::length($number) === 12) {
            $number = preg_replace('/^\+?91/', '', $number);
        }
        // remove + from country code
        $countryCode = preg_replace('/^\+/', '', $countryCode);
        // prepend country code
        return $countryCode . $number;
    }
}