<?php
/**
* aR Captcha - Protect Form v4
* @copyright © 2007-2025 alexandr Belov aka alex Roosso.
* @author    alex Roosso <info@roocms.com>
* @link      http://www.roocms.com
* @license   MIT
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
* FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
* COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
* IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
* CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/


class aRCaptcha {

	# code
	private static $code = "00000";
	private static $code_length = 5;
	private static $letter_width = 0;

	# options for string
	private static $use_number       = true;
	private static $use_upper_letter = true;
	private static $use_lower_letter = false;

	# options for images

	# bg color
	private static $bgcolor = array(254, 254, 254);

	# sizes
	private static $width   = 180;
	private static $height  = 85;

	# user settings
	private static $use_polygons   = false;
	private static $use_fontsnoise = true;
	private static $shuffle_font   = false;

	# palette
	private static $palette = "aR-Captcha";
	private static $randoms = array(0,20);

	# fonts path
	private static $font_path = "fonts";


	/**
	 * Show
	 *
	 * @param string $code
	 * @return void
	 * @throws Exception
	 */
	public static function show(string $code="000000"): void {

		self::set_code($code);

		# get
		$captcha = self::captcha();

		# validate image resource
		if (!$captcha || !is_object($captcha)) {
			throw new Exception("Failed to create captcha image");
		}

		# draw
		header("Content-type: image/jpeg");
		if (!imagejpeg($captcha)) {
			throw new Exception("Failed to output captcha image");
		}
		imagedestroy($captcha);
	}


	/**
	 * CRAFT CAPTCHA
	 *
	 * @return GdImage|false
	 * @throws Exception
	 */
	private static function captcha() {

		$captcha = imagecreatetruecolor(self::$width, self::$height);
		if (!$captcha) {
			throw new Exception("Failed to create truecolor image");
		}

		$bg = imagecolorallocate($captcha, self::$bgcolor[0], self::$bgcolor[1], self::$bgcolor[2]);
		if ($bg === false) {
			throw new Exception("Failed to allocate background color");
		}

		if (!imagefill($captcha, 0, 0, $bg)) {
			throw new Exception("Failed to fill image background");
		}

		# NOISE
		if(self::$use_polygons && $captcha instanceof GdImage) {
			$captcha = self::polygons($captcha);
		}

		if(self::$use_fontsnoise && $captcha instanceof GdImage) {
			$captcha = self::fontsnoise($captcha);
		}

		# letters
		$captcha = self::letters($captcha);

		return $captcha;
	}


	/**
	 * Set code string on image (use ttf font)
	 *
	 * @param GdImage $captcha - image resource
	 *
	 * @return GdImage
	 * @throws Exception
	 */
	private static function letters(GdImage $captcha): GdImage {

		# get font
		$font = self::get_font();

		$shift = 4;
		for($l=0;$l<=self::$code_length-1;$l++) {
			list($r,$g,$b) = self::get_random_rgb();
			$color = imagecolorallocatealpha($captcha, $r, $g, $b, mt_rand(0,25));
			//$colorsh  = imagecolorallocatealpha($captcha, $r/2, $g/2, $b/2, mt_rand(25,50));

			if ($color === false) {
				throw new Exception("Failed to allocate text color");
			}

			$angle = mt_rand(-15,15);

			$y = mt_rand(round(self::$height/1.4), self::$height);
			$size = mt_rand(floor(self::$height/2.25), ceil(self::$height/1.20));

			$letter = mb_substr(self::$code, $l, 1);

			switch($l) {
				case 0:
					$position = mt_rand($shift - round(self::$letter_width * .05), $shift + round(self::$letter_width * .25));
					break;

				case self::$code_length-1:
					$position = mt_rand($shift - round(self::$letter_width * .25), $shift + round(self::$letter_width * .05));
					break;

				default:
					$position = mt_rand($shift - round(self::$letter_width * .25), $shift + round(self::$letter_width * .25));
					break;
			}

			//imagettftext($captcha, $size, $angle, $position, $y-1, $colorsh, $font['file'], $letter);
			$result = imagettftext($captcha, $size, $angle, $position, $y, $color, $font['file'], $letter);

			if ($result === false) {
				throw new Exception("Failed to render text with font: " . $font['file']);
			}

			if(self::$shuffle_font) {
				$font = self::get_font();
			}

			$shift += self::$letter_width;
		}

		return $captcha;
	}


	/**
	 * Draw lines on captcha background
	 *
	 * @param GdImage $captcha
	 *
	 * @return GdImage
	 * @throws Exception
	 */
	private static function polygons(GdImage $captcha): GdImage {

		$min = min(self::$width, self::$height);
		$max = max(self::$width, self::$height);

		$scream = mt_rand(1,self::$code_length);


		for($i=0;$i<=$scream;$i++) {
			list($r,$g,$b) = self::get_random_rgb();
			$color = imagecolorallocatealpha($captcha, $r, $g, $b, 10);

			if ($color === false) {
				throw new Exception("Failed to allocate polygon color");
			}

			$points = array();
			for($s=0;$s<=2;$s++) {
				$points[] = mt_rand(0,self::$width); mt_srand();
				$points[] = mt_rand(0,self::$height); mt_srand();
			}

			if (!imagesetthickness($captcha, mt_rand(3,5))) {
				throw new Exception("Failed to set image thickness");
			}

			if (!imagepolygon($captcha, $points, 3, $color)) {
				throw new Exception("Failed to draw polygon");
			}
		}

		return $captcha;
	}


	/**
	 * Draw letters on BG Captcha
	 *
	 * @param GdImage $captcha
	 *
	 * @return GdImage
	 * @throws Exception
	 */
	private static function fontsnoise(GdImage $captcha): GdImage {

		# get font
		$font = self::get_font();

		$shift = 4;
		for($l=0;$l<=self::$code_length-1;$l++) {
			list($r,$g,$b) = self::get_random_rgb();
			$color = imagecolorallocatealpha($captcha, $r, $g, $b, mt_rand(85,95));

			if ($color === false) {
				throw new Exception("Failed to allocate noise text color");
			}

			$angle = mt_rand(-5,5);

			$y = mt_rand(round(self::$height/1.5), ceil(self::$height*2));
			$size = mt_rand(floor(self::$height/1.1), ceil(self::$height*1.5));

			$letter = mb_substr(self::$code, mt_rand(0,self::$code_length-1), 1);

			$position = mt_rand($shift - round(self::$letter_width * .5), $shift + round(self::$letter_width * .5));

			$result = imagettftext($captcha, $size, $angle, $position, $y, $color, $font['file'], $letter);

			if ($result === false) {
				throw new Exception("Failed to render noise text with font: " . $font['file']);
			}

			if(self::$shuffle_font) {
				$font = self::get_font();
			}

			$shift += self::$letter_width;
		}

		return $captcha;
	}


	/**
	 * Valid and set code string
	 *
	 * @param string $code
	 * @return void
	 * @throws Exception
	 */
	private static function set_code(string $code): void {

		$condition = self::get_condition();

		$code = preg_replace(array('(\W+)','([^'.$condition.'])'), array('',''), $code);

		if (empty($code)) {
			throw new Exception("Invalid or empty captcha code");
		}

		self::$code = $code;
		self::$code_length = mb_strlen(self::$code);
		self::$letter_width = (self::$width / self::$code_length >= self::$height) ? self::$height : (self::$width / self::$code_length) - 4;
	}


	/**
	 * Get random ttf font
	 *
	 * @return array
	 * @throws Exception
	 */
	private static function get_font(): array {

		# Validate font path to prevent path traversal
		$font_path = realpath(dirname(__FILE__)."/".self::$font_path);
		if (!$font_path || !is_dir($font_path)) {
			throw new Exception("Invalid font path: " . self::$font_path);
		}

		# Select random fonts
		$fonts = glob($font_path."/*.ttf", GLOB_BRACE);

		# Check if fonts array is not empty
		if (empty($fonts)) {
			throw new Exception("No fonts found in path: " . $font_path);
		}

		# choice font
		$font = array();
		$font['file'] = $fonts[mt_rand(0,count($fonts)-1)];

		return $font;
	}


	/**
	 * Get random HEX RGB color
	 *
	 * @return array
	 */
	private static function get_random_rgb(): array {

		mt_srand();
		$colorvariator = md5(self::$palette . mt_rand(self::$randoms[0], self::$randoms[1]));

		return array(
			hexdec(substr($colorvariator, 0, 2)),
			hexdec(substr($colorvariator, 2, 2)),
			hexdec(substr($colorvariator, 4, 2))
		);
	}


	/**
	 * Get pcre condition for code string
	 *
	 * @return string
	 */
	private static function get_condition(): string {

		$condition = "";

		if(self::$use_lower_letter) {
			$condition .= "a-z";
		}
		if(self::$use_upper_letter) {
			$condition .= "A-Z";
		}
		if(self::$use_number) {
			$condition .= "0-9";
		}

		return $condition;
	}


	/**
	 * Debug function for view palette
	 * @return void
	 * @throws Exception
	 */
	public static function palette(): void {

		$nums = range(self::$randoms[0], self::$randoms[1]);

		$scale = 30;

		$im = imagecreatetruecolor($scale * count($nums), $scale);

		if (!$im) {
			throw new Exception("Failed to create palette image");
		}

		$shift = 0;
		foreach ($nums as $num) {

			$color = md5(self::$palette . $num);

			$r = hexdec(substr($color, 0, 2));
			$g = hexdec(substr($color, 2, 2));
			$b = hexdec(substr($color, 4, 2));

			$c = imagecolorallocate($im, $r, $g, $b);
			if ($c === false) {
				throw new Exception("Failed to allocate palette color");
			}

			if (!imagefilledrectangle($im, $scale * $shift, 0, $scale * ($shift + 1), $scale, $c)) {
				throw new Exception("Failed to draw palette rectangle");
			}
			$shift++;
		}

		header('Content-Type: image/png');
		if (!imagepng($im)) {
			throw new Exception("Failed to output palette image");
		}
		imagedestroy($im);
	}
}
