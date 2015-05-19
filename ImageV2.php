<?php

/*

Copyright 2014 William Whitty
will.whitty.arbeit@gmail.com

Licensed under the Apache License, Version 2.0 (the 'License');
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an 'AS IS' BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

*/

if (defined('TAVURTH_IMAGE_V2') == FALSE) {
	define('TAVURTH_IMAGE_V2', TRUE);
	
	include '/home/tavurth/source/Source.php';
	
	include 'ColorTool.php';
	
	class Image {
		protected $image_;
		protected $colors;
		protected $font = '/home/tavurth/usr/data/fonts/Inconsolata-Bold.ttf';
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	EXTERNAL / INTERNAL CONSTRUCTION AND COMPLETION
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public function __construct($width = FALSE, $height = FALSE) {
			if ($width && $height) {
				$this->image_set_new($width, $height);
				$this->init_colors();
			}
		}
		public static function create_from_png($location) {
			$image = new Image();
			$image->image_set(imagecreatefrompng($location));
			$image->init_colors();
			return $image;
		}
		public static function create_from_jpg($location) {
			$image = new Image();
			$image->image_set(imagecreatefromjpeg($location));
			$image->init_colors();
			return $image;
		}
		public static function create_from_gd($gdImage) {
			$image = new Image();
			$image->image_set($gdImage);
			$image->init_colors();
			return $image;
		}
		public static function create_from_data($data) {
			$image = new Image();
			$image->image_set(imagecreatefromstring($data));
			$image->init_colors();
			return $image;
		}
		public function copy_from_gd($gdImage) {
			$this->image_set_new(imagesx($gdImage), imagesy($gdImage));
			$this->copy_gd($gdImage);
			$this->init_colors();
		}
		public function fill_from_gd($gdImage) {
			$this->image_set($gdImage);
			$this->copy_gd($gdImage);
			$this->init_colors();
		}
		public function fill($width, $height) {
			$this->image_set_new($width, $height);
			$this->init_colors();
		}
		public static function create_from_url($url) {
			return Image::create_from_gd(Image::gd_from_url($url));
		}
		public function fill_from_url($url) {
			return $this->fill_from_gd(Image::gd_from_url($url));
		}
		public static function gd_from_url($url) {
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.001 (windows; U; NT4.0; en-US; rv:1.0) Gecko/25250101');
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,1);
			$data = curl_exec($ch);
			
			if (strlen($data) < 1)
				return false;
			
			$output = @imagecreatefromstring($data);
			curl_close($ch);
			
			return $output;
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	VARIABLE ACCESS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public function line_set($thick) { imagesetthickness($this->image_, $thick); }
		public function width() { return imagesx($this->image_); }
		public function height() { return imagesy($this->image_); }
		public function image() { return $this->image_; }
		public function image_set($newI) { $this->image_ = $newI; }
		public function image_set_new($width, $height) { $this->image_ = imagecreatetruecolor($width, $height); }
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	COLORS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		private function init_colors() {
			$this->colors = array();
			$this->color_add('WHITE', 255,255,255);
			$this->color_add('BLACK', 0,0,0);
			$this->color_add('ALPHA', 0, 0, 0, 127);
			$this->color_add('ALPHA+', 0, 255, 255, 0);
			$this->color_transparent('ALPHA+');
			
			ColorTool::palette_load($this);
			
			if (isset($this->colors['BACKG']) === FALSE)
				$this->background('BLACK');
		}
		public function color($name) {
			if (is_numeric($name))
				return $name; 
			return (isset($this->colors[$name]) ? $this->colors[$name] : FALSE); 
		}
		public function color_add($name, $r, $g, $b, $a=FALSE) { 
			if ($this->image_) {
				if (isset($this->colors[$name]))
					$this->color_remove($name);
				switch (func_num_args()) {
					case 4: $this->colors[$name] = imagecolorallocate($this->image_, $r, $g, $b); break;
					case 5: $this->colors[$name] = imagecolorallocatealpha($this->image_, $r, $g, $b, $a); 
				}
				return $this->colors[$name];
			}
			return FALSE;
		}
		public function color_remove($name) {
			imagecolordeallocate($this->image_, $this->color($name));
			unset($this->colors[$name]);
		}
		public function background($color) {
			$this->draw_rect(0,0, $this->width(), $this->height(), $color);
			$this->colors['BACKG'] = $this->color($color);
		}
		public function color_closest($red, $green, $blue) {
			return imagecolorclosest($this->image_, $red, $green, $blue);
		}
		public function colors_for_index($color) {
			return imagecolorsforindex($this->image_, $this->color($color));
		}
		public function colors_for_index_gd($color) {
			return imagecolorsforindex($this->image_, $color);
		}
		public function color_set_rgb($colorIndex, $red, $green, $blue) {
			imagecolorset($this->image_, $colorIndex, $red, $green, $blue); 
		}
		public function color_transparent($color) {
			imagecolortransparent($this->image_, $this->color($color));
		}
		public function color_set($index, $color) {
			$colors = $this->colors_for_index($color);
			$this->color_set_rgb($index, $colors['red'], $colors['green'], $colors['blue']);
		}
		public function color_at($x, $y) {
			return imagecolorat($this->image_, max(0, min($this->width()-1, $x)), max(0, min($this->height()-1, $y)));
		}
		public function colors_at($x, $y) {
			return $this->colors_for_index($this->color_at($x, $y));
		}
		public function color_set_pixel($x, $y, $r, $g, $b, $a = FALSE) {
			switch (func_num_args()) {
				case 6: $rgb = imagecolorallocatealpha($this->image_, $r, $g, $b, $a); break;
				default: $rgb = imagecolorallocate($this->image_, $r, $g, $b); 
			}
			$this->set_pixel($x,$y,$rgb);
			imagecolordeallocate($this->image_, $rgb);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	FINALIZATION
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public function base64_encode($type = 'png') {
			ob_start();
			$this->finalize($type, NULL, FALSE);
			$dataBuffer = ob_get_contents();
			ob_end_clean();
			
			return base64_encode($dataBuffer);
		}
		
		// Use this function to finalize the image before returning from file.
		public function finalize($type = 'png', $file = NULL, $header = TRUE) {
			if ($header)
				header('Content-Type: image/'.$type);
			switch ($type) {
				case 'png':
					return imagepng($this->image_, $file);
					break;
				case 'jpg':
					return imagejpeg($this->image_, $file);
					break;
				default:
					return;
			}
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	DRAWING SIMPLE
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public function set_pixel($x, $y, $color) {
			imagesetpixel($this->image_, $x, $y, $this->color($color));
		}
		public function draw_line($x, $y, $x2, $y2, $color) {
			imageline($this->image_, $x, $y, $x2, $y2, (is_numeric($color) ? $color : $this->color($color)));
		}
		public function draw_rect($x, $y, $x2, $y2, $color) {
			imagefilledrectangle($this->image_, $x, $y, $x2, $y2, $this->color($color));
		}
		public function fill_polygon(array $positions,$color) {
			imagefilledpolygon($this->image_, $positions, count($positions)/2, $this->color($color));
		}
		public function draw_polygon(array $positions,$color) {
			imagepolygon($this->image_, $positions, count($positions)/2, $this->color($color));
		}
		public function draw_triangle($x1,$y1,$x2,$y2,$x3,$y3,$color) {
			imagefilledpolygon($this->image_, array($x1,$y1,$x2,$y2,$x3,$y3), 3, $this->color($color));
		}
		public function draw_ellipse($x, $y, $width, $height, $color) {
			imagefilledellipse($this->image_, $x, $y, $width, $height, $this->color($color));
		}
		public function draw_string($font, $x, $y, $string, $color) {
			imagestring($this->image_, $font, $x, $y, $string, $this->color($color));
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	STRING FUNCTIONS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public function draw_ttf_string($size, $x, $y, $string, $color, $angle = 0) {
		//Draw a string to the screen using a TTF
			if (!file_exists($this->font))
				{ echo('Filename ' . $this->font . ' not found.'); return; }
			
			if (function_exists('imagettftext'))
				imagettftext($this->image_, $size, $angle, $x, $y, $this->color($color), $this->font, $string);
		}
		public function ttf_size($size, $string, $angle = 0) {
		//Return an array of the string bounding box for a TTF
			//	0	lower left corner, X position
			//	1	lower left corner, Y position
			//	2	lower right corner, X position
			//	3	lower right corner, Y position
			//	4	upper right corner, X position
			//	5	upper right corner, Y position
			//	6	upper left corner, X position
			//	7	upper left corner, Y position
			
			if (function_exists('imagettfbbox'))
				return imagettfbbox($size, $angle, $this->font, $string);
			else 
				return FALSE;
		}
		public function size_ttf($size) {
			return $size*(72/96);
		}
		public function ttf_width($size, $string, $angle = 0) {
			$sizeArray = self::ttf_size($size, $string, $angle);
			return abs($sizeArray[4] - $sizeArray[0]);
		}
		public function ttf_height($size, $string, $angle = 0) {
			$sizeArray = self::ttf_size($size, $string, $angle);
			return abs($sizeArray[5] - $sizeArray[1]);
		}
		public function draw_string_bg($font, $x, $y, $string, $color, $colorB) {
			$this->draw_rect($x-5, $y-5, $x+($font*1.9)*strlen($string), $y+$font*4, $colorB);
			$this->draw_string($font, $x, $y, $string, $color);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	STYLE FUNCTIONS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public function set_style($colors) {
			$gdColors = array();
			foreach ($colors as $color)
				array_push($gdColors, $this->color($color));
			imagesetstyle($this->image_, $gdColors);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	DRAWING COMPLEX FUNCTIONS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public function draw_rounded_rect($x1, $y1, $x2, $y2, $radius, $color) {
			// draw rectangle without corners
			imagefilledrectangle($this->image_, $x1+$radius, $y1, $x2-$radius, $y2, $this->color($color));
			imagefilledrectangle($this->image_, $x1, $y1+$radius, $x2, $y2-$radius, $this->color($color));
			// draw circled corners
			imagefilledellipse($this->image_, $x1+$radius, $y1+$radius, $radius*2, $radius*2, $this->color($color));
			imagefilledellipse($this->image_, $x2-$radius, $y1+$radius, $radius*2, $radius*2, $this->color($color));
			imagefilledellipse($this->image_, $x1+$radius, $y2-$radius, $radius*2, $radius*2, $this->color($color));
			imagefilledellipse($this->image_, $x2-$radius, $y2-$radius, $radius*2, $radius*2, $this->color($color));
		}
		public function draw_bevelled_rect($x1, $y1, $x2, $y2, $radius, $colorA, $colorB) {
			$this->draw_rounded_rect($x1, $y1, $x2, $y2, $radius, $colorB);
			$this->draw_rect($x1+$radius/2, $y1+$radius/2, $x2, $y2, $colorA);
		}
		public function draw_menu($x1, $y1, $x2, $y2, $radius, $colorA, $colorB) {
			$this->draw_rounded_rect($x1, $y1, $x2, $y2, $radius, $colorB);
			$this->draw_rounded_rect($x1+$radius/2, $y1+$radius/2, $x2-$radius/2, $y2-$radius/2, $radius, $colorA);
		}
		public function draw_border($paddingX1, $paddingY1, $paddingX2, $paddingY2, $color) {
			$this->line_set(2);
			//Right rectangle
			$this->draw_rect($this->width()-$paddingX2, 0, $this->width(), $this->height(), 'BACKG');
			$this->draw_rect($paddingX1, $this->height(), 0, 0, 'BACKG');
			//Bottom
			$this->draw_rect(0, $this->height()-$paddingY2, $this->width(), $this->height(), 'BACKG');
			$this->draw_rect(0, 0, $this->width(), $paddingY1, 'BACKG');
			
			$this->draw_line($this->width()-$paddingX2, 
											$paddingY1, 
											$this->width()-$paddingX2, 
											$this->height()-$paddingY2,
											$color);
			$this->draw_line($this->width()-$paddingX2, 
											$this->height()-$paddingY2,
											$paddingX1,
											$this->height()-$paddingY2, 
											$color);
			$this->draw_line($paddingX1,
											$paddingY1,
											$paddingX1, 
											$this->height()-$paddingY2,
											$color);
			$this->draw_line($paddingX1,
								 			$paddingY1,
											$this->width()-$paddingX2,
											$paddingY1,
											$color);
		}
		
		public function round_corners($size, $color='ALPHA+') {
			$h = $size/3.5;
			$v = $size/3.5;
			
			$this->draw_ellipse(-$h, -$v, $size, $size, $color);
			$this->draw_ellipse($this->width()+$h, -$v, $size, $size, $color);
			$this->draw_ellipse($this->width()+$h, $this->height()+$v, $size, $size, $color);
			$this->draw_ellipse(-$h, $this->height()+$v, $size, $size, $color);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	COPY FUNCTIONS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public function copy_resize_gd($image, $x, $y, $sx, $sy, $w, $h, $sw, $sh) {
			imagecopyresized($this->image_, $image, $x, $y, $sx, $sy, $w, $h, $sw, $sh);
		}
		public function copy_resize($source, $sx, $sy, $sw, $sh, $x, $y, $w, $h) {
			$this->copy_resize_gd($source->image(), $x, $y, $sx, $sy, $w, $h, $sw, $sh);
		}
		public function copy_portion($source, $sx, $sy, $sw, $sh) {
			$this->copy_resize($source, $sx, $sy, $sw, $sh, 0, 0, $this->width(), $this->height());
		}
		public function copy_merge_gd($source, $alpha) {
			imagecopymerge($this->image_, $source, 0, 0, 0, 0, imagesx($source), imagesy($source), $alpha);
		}
		public function copy_merge($source, $alpha) {
			imagecopymerge($this->image_, $source->image(), 0, 0, 0, 0, $source->width(), $source->height(), $alpha);
		}
		function copy_merge_alpha($source, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){ 
			$src_im = $source->image();
	        // creating a cut resource 
	        $cut = imagecreatetruecolor($src_w, $src_h); 
	
	        // copying relevant section from background to the cut resource 
	        imagecopy($cut, $this->image_, 0, 0, $dst_x, $dst_y, $src_w, $src_h); 
	        
	        // copying relevant section from watermark to the cut resource 
	        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h); 
	        
	        // insert cut resource to destination image 
	        imagecopymerge($this->image_, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct); 
	    } 
		public function copy_gd($source) {
			imagecopy($this->image_, $source, 0, 0, 0, 0, imagesx($source), imagesy($source));
		}
		public function copy($source) {
			imagecopy($this->image_, $source->image(), 0, 0, 0, 0, $this->width(), $this->height());
		}
		public function copy_alpha($source, $percent) {
			$this->copy_merge_alpha($source, 0,0,0,0,$this->width(), $this->height(), $percent);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	TILING FUNCTIONS
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public function tile_gd($tile, $x, $y, $w, $h) {
			$tiled = imageCreateTrueColor($w, $h);
			imagesettile($tiled, $tile);
			imageFilledRectangle($tiled, 0, 0, $w, $h, IMG_COLOR_TILED);
			$this->copy_resize_gd($tiled, $x, $y, 0, 0, $w, $h, $w, $h);
			imagedestroy($tiled);
		}
		public function tile_loc($loc) {
			$tile = imagecreatefrompng($loc);
			$this->tile_gd($tile, 0, 0, $this->width(), $this->height());
			imagedestroy($tile);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	TRANSFORMATION
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		public function antialias($bool) {
			if (function_exists('imageantialias'))
				imageantialias($this->image_, $bool);
		}
		public function gamma($input, $output) {
			imagegammacorrect($this->image_, $input, $output);
		}
		public function filter($filter, $arg1=NULL, $arg2=NULL, $arg3=NULL) {
			if (function_exists('imagefilter'))
				switch (func_num_args()) {
					case 1: imagefilter($this->image_,$filter); break;
					case 2: imagefilter($this->image_,$filter,$arg1); break;
					case 3: imagefilter($this->image_,$filter,$arg1,$arg2); break;
					case 4: imagefilter($this->image_,$filter,$arg1,$arg2,$arg3); break;
				}
			else 
				echo 'Could not utilize function imagefilter<br>';
		}
        public function rotate($degrees, $color='BLACK') {
			if (($img = imagerotate($this->image_, 360-$degrees, $this->color($color))) != NULL) {
				imagedestroy($this->image_);
				$this->image_ = $img;
			}
		}
		public function flip() {
		//
		//	SOURCE: http://maettig.com/?page=PHP/imageflip
		//	MODIFIED
		//
			$this->rotate(180, 'BACKG');
			$image = $this->image_;
			$width  = imagesx($image);
			$height = imagesy($image);
			
			// Truecolor provides better results, if possible.
			if (function_exists('imageistruecolor') && imageistruecolor($image))
				$tmp = imagecreatetruecolor(1, $height);
			else
				$tmp = imagecreate(1, $height);
			
			$x2 = $width - 1;
			for ($i = (int) floor(($width - 1) / 2); $i >= 0; $i--) {
			// Backup right stripe.
			imagecopy($tmp,   $image, 0,        0,  $x2 - $i, 0, 1, $height);
			// Copy left stripe to the right.
			imagecopy($image, $image, $x2 - $i, 0, $i,  0, 1, $height);
			// Copy backuped right stripe to the left.
			imagecopy($image, $tmp,   $i,  0,   0,       0,  1, $height);
			}
			
			imagedestroy($tmp);
		}
		
		//////////////////////////////////////////////////////////////////////////////////
		//
		//	CLEANUP
		//
		//////////////////////////////////////////////////////////////////////////////////
		
		protected function destroy_colors() {
			foreach ($this->colors as $key => $value) {
				imagecolordeallocate($this->image_, $value);
				$key = NULL;
			}
		}
		
		public function __destruct() {
			if ($this->image_) {
				$this->destroy_colors();
				imagedestroy($this->image_);
			}
		}
	}
}

?>