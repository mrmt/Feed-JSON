<?php
if ( !class_exists( 'My_Services_JSON' ) ) :
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/**
 * Converts to and from JSON format.
 *
 * JSON (JavaScript Object Notation) is a lightweight data-interchange
 * format. It is easy for humans to read and write. It is easy for machines
 * to parse and generate. It is based on a subset of the JavaScript
 * Programming Language, Standard ECMA-262 3rd Edition - December 1999.
 * This feature can also be found in  Python. JSON is a text format that is
 * completely language independent but uses conventions that are familiar
 * to programmers of the C-family of languages, including C, C++, C#, Java,
 * JavaScript, Perl, TCL, and many others. These properties make JSON an
 * ideal data-interchange language.
 *
 * This package provides a simple encoder and decoder for JSON notation. It
 * is intended for use with client-side Javascript applications that make
 * use of HTTPRequest to perform server communication functions - data can
 * be encoded into JSON notation for use in a client-side javascript, or
 * decoded from incoming Javascript requests. JSON format is native to
 * Javascript, and can be directly eval()'ed with no further parsing
 * overhead
 *
 * All strings should be in ASCII or UTF-8 format!
 *
 * LICENSE: Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the following
 * conditions are met: Redistributions of source code must retain the
 * above copyright notice, this list of conditions and the following
 * disclaimer. Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following disclaimer
 * in the documentation and/or other materials provided with the
 * distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
 * NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @category
 * @package		My_Services_JSON
 * @author		Michal Migurski <mike-json@teczno.com>
 * @author		Matt Knapp <mdknapp[at]gmail[dot]com>
 * @author		Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
 * @copyright	2005 Michal Migurski
 * @version     CVS: $Id: JSON.php 288200 2009-09-09 15:41:29Z alan_k $
 * @license		http://www.opensource.org/licenses/bsd-license.php
 * @link		http://pear.php.net/pepr/pepr-proposal-show.php?id=198
 */

/**
 * Marker constant for My_Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_SLICE', 1);

/**
 * Marker constant for My_Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_STR',  2);

/**
 * Marker constant for My_Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_ARR',  3);

/**
 * Marker constant for My_Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_OBJ',  4);

/**
 * Marker constant for My_Services_JSON::decode(), used to flag stack state
 */
define('SERVICES_JSON_IN_CMT', 5);

/**
 * Behavior switch for My_Services_JSON::decode()
 */
define('SERVICES_JSON_LOOSE_TYPE', 16);

/**
 * Behavior switch for My_Services_JSON::decode()
 */
define('SERVICES_JSON_SUPPRESS_ERRORS', 32);

/**
 * Converts to and from JSON format.
 *
 * Brief example of use:
 *
 * <code>
 * // create a new instance of My_Services_JSON
 * $json = new My_Services_JSON();
 *
 * // convert a complexe value to JSON notation, and send it to the browser
 * $value = array('foo', 'bar', array(1, 2, 'baz'), array(3, array(4)));
 * $output = $json->encode($value);
 *
 * print($output);
 * // prints: ["foo","bar",[1,2,"baz"],[3,[4]]]
 *
 * // accept incoming POST data, assumed to be in JSON notation
 * $input = file_get_contents('php://input', 1000000);
 * $value = $json->decode($input);
 * </code>
 */
class My_Services_JSON
{
 /**
	* constructs a new JSON instance
	*
	* @param int $use object behavior flags; combine with boolean-OR
	*
	*						possible values:
	*						- SERVICES_JSON_LOOSE_TYPE:  loose typing.
	*								"{...}" syntax creates associative arrays
	*								instead of objects in decode().
	*						- SERVICES_JSON_SUPPRESS_ERRORS:  error suppression.
	*								Values which can't be encoded (e.g. resources)
	*								appear as NULL instead of throwing errors.
	*								By default, a deeply-nested resource will
	*								bubble up with an error, so all return values
	*								from encode() should be checked with isError()
	*/
	function My_Services_JSON($use = 0)
	{
		$this->use = $use;
	}

 /**
	* encodes an arbitrary variable into JSON format (and sends JSON Header)
	*
	* @param	mixed $var	any number, boolean, string, array, or object to be encoded.
	*						see argument 1 to My_Services_JSON() above for array-parsing behavior.
	*						if var is a strng, note that encode() always expects it
	*						to be in ASCII or UTF-8 format!
	*
	* @return mixed JSON string representation of input var or an error if a problem occurs
	* @access public
	*/
	function encode($var)
	{
		header('Content-type: application/json');
		return $this->_encode($var);
	}
	/**
	* encodes an arbitrary variable into JSON format without JSON Header - warning - may allow CSS!!!!)
	*
	* @param	mixed $var	any number, boolean, string, array, or object to be encoded.
	*						see argument 1 to My_Services_JSON() above for array-parsing behavior.
	*						if var is a strng, note that encode() always expects it
	*						to be in ASCII or UTF-8 format!
	*
	* @return mixed JSON string representation of input var or an error if a problem occurs
	* @access public
	*/
	function encodeUnsafe($var)
	{
		return $this->_encode($var);
	}
	/**
	* PRIVATE CODE that does the work of encodes an arbitrary variable into JSON format
	*
	* @param	mixed $var	any number, boolean, string, array, or object to be encoded.
	*						see argument 1 to My_Services_JSON() above for array-parsing behavior.
	*						if var is a strng, note that encode() always expects it
	*						to be in ASCII or UTF-8 format!
	*
	* @return mixed JSON string representation of input var or an error if a problem occurs
	* @access public
	*/
	function _encode($var)
	{

		switch (gettype($var)) {
			case 'boolean':
				return $var ? 'true' : 'false';

			case 'NULL':
				return 'null';

			case 'integer':
				return (int) $var;

			case 'double':
			case 'float':
				return (float) $var;

			case 'string':
				// STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
				$ascii = '';
				$strlen_var = strlen($var);

			/*
				* Iterate over every character in the string,
				* escaping with a slash or encoding to UTF-8 where necessary
				*/
				for ($c = 0; $c < $strlen_var; ++$c) {

					$ord_var_c = ord($var{$c});

					switch (true) {
						case $ord_var_c == 0x08:
							// $ascii .= '\b';
							break;
						case $ord_var_c == 0x09:
							// $ascii .= '\t';
							break;
						case $ord_var_c == 0x0A:
							$ascii .= '\n';
							break;
						case $ord_var_c == 0x0C:
							// $ascii .= '\f';
							break;
						case $ord_var_c == 0x0D:
							// $ascii .= '\r';
							break;

						case $ord_var_c == 0x22:
						// case $ord_var_c == 0x2F:
						// case $ord_var_c == 0x5C:
							// double quote, slash, slosh
							$ascii .= '\\'.$var{$c};
							break;

						default:
							$ascii .= $var{$c};
							break;
					}
				}
				return  '"'.$ascii.'"';

			case 'array':
			/*
				* As per JSON spec if any array key is not an integer
				* we must treat the the whole array as an object. We
				* also try to catch a sparsely populated associative
				* array with numeric keys here because some JS engines
				* will create an array with empty indexes up to
				* max_index which can cause memory issues and because
				* the keys, which may be relevant, will be remapped
				* otherwise.
				*
				* As per the ECMA and JSON specification an object may
				* have any string as a property. Unfortunately due to
				* a hole in the ECMA specification if the key is a
				* ECMA reserved word or starts with a digit the
				* parameter is only accessible using ECMAScript's
				* bracket notation.
				*/

				// treat as a JSON object
				if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
					$properties = array_map(array($this, 'name_value'),
											array_keys($var),
											array_values($var));

					foreach($properties as $property) {
						if(My_Services_JSON::isError($property)) {
							return $property;
						}
					}

					if($var['force_array']){
						return "[\n{\n" . join(",\n", $properties) . "\n}\n]";
					}else{
						return '{' . "\n" . join(",\n", $properties) . "\n" . '}';
					}
				}

				// treat it like a regular array
				$elements = array_map(array($this, '_encode'), $var);

				foreach($elements as $element) {
					if(My_Services_JSON::isError($element)) {
						return $element;
					}
				}

				return '[' . "\n" . join(',', $elements) . "\n" . ']';

			case 'object':
				$vars = get_object_vars($var);

				$properties = array_map(array($this, 'name_value'),
										array_keys($vars),
										array_values($vars));

				foreach($properties as $property) {
					if(My_Services_JSON::isError($property)) {
						return $property;
					}
				}

				return '{' . join(',', $properties) . '}';

			default:
				return ($this->use & SERVICES_JSON_SUPPRESS_ERRORS)
					? 'null'
					: new My_Services_JSON_Error(gettype($var)." can not be encoded as JSON string");
		}
	}

 /**
	* array-walking function for use in generating JSON-formatted name-value pairs
	*
	* @param	string  $name name of key to use
	* @param	mixed $value  reference to an array element to be encoded
	*
	* @return string  JSON-formatted name-value pair, like '"name":value'
	* @access private
	*/
	function name_value($name, $value)
	{
		$encoded_value = $this->_encode($value);

		if(My_Services_JSON::isError($encoded_value)) {
			return $encoded_value;
		}

		return $this->_encode(strval($name)) . ' : ' . $encoded_value;
	}

	/**
	* @todo Ultimately, this should just call PEAR::isError()
	*/
	function isError($data, $code = null)
	{
		if (class_exists('pear')) {
			return PEAR::isError($data, $code);
		} elseif (is_object($data) && (get_class($data) == 'services_json_error' ||
								is_subclass_of($data, 'services_json_error'))) {
			return true;
		}

		return false;
	}
}

if (class_exists('PEAR_Error')) {

	class My_Services_JSON_Error extends PEAR_Error
	{
		function My_Services_JSON_Error($message = 'unknown error', $code = null,
									$mode = null, $options = null, $userinfo = null)
		{
			parent::PEAR_Error($message, $code, $mode, $options, $userinfo);
		}
	}

} else {

	/**
	* @todo Ultimately, this class shall be descended from PEAR_Error
	*/
	class My_Services_JSON_Error
	{
		function My_Services_JSON_Error($message = 'unknown error', $code = null,
									$mode = null, $options = null, $userinfo = null)
		{

		}
	}

}
endif;
?>
