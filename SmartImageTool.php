<?php

/**
 * FR : Classe détectant automatiquement la zone la plus intéressante dans une image par exemple pour la cropper.
 *      Elle utilise pour cela la variation de couleur entre chaque pixel.
 *
 * EN : This class automatically find the most interesting zone in a picture in order to crop it.
 *      It uses the color variations between each pixel.
 * 
 * @author     Xavier Langlois aka XL714 <xavier.langlois@gmail.com>
 * @version    Release: 1.0
 * @link       http://todo
 * @see        NetOther, Net_Sample::Net_Sample()
 * @since      Class available since Release 1.2.0
 */

class SmartImageTool
{
    public $saveDir;

    public $originalImage;
    public $originalImagePath;
    public $originalImageRatio;
    public $originalImageFileInfos;
    public $originalImageWidth;
    public $originalImageHeight;

    public $tmpImage;
    public $tmpImagePath;
    public $tmpImageWidth = 40;
    public $tmpImageHeight;
    public $tmpImageRatio;
    public $tmpImageSuffix = '_SmartImageToolTmpImage';
    public $tmpImageMatrix = array();
    public $tmpImageVariationsMatrix = array();
    public $tmpImagevariationsCentroid;

    public $useFilterGrayScale = true;
    public $useFilterContrast = true;
    public $filterContrast = -200;



    public $finalImage;
    public $finalImagePath;
    public $finalImageRatio = 1; // e.g portrait = 0.66, landscape  = 1.5, square = 1
    public $finalImageWidth;
    public $finalImageHeight;

    public $heaviestZoneStartX = false; // as %
    public $heaviestZoneStartY = false; // as %

    public $errors = array();

    public $flagColor;

    /**
     * @parameter $source: image file path or http post image array (png or jpg)
     */
    public static function instance($source = false){
        return new self($source);
    }

    public function __construct($source){
        
        $extension = false;

        if(is_array($source) && !empty($source['tmp_name'])  && !empty($source['type'])){
            $this->originalImagePath = $source['tmp_name'];
            $extensionByType = array('image/jpeg'=>'jpg', 'image/png'=> 'png');
            if(isset($extensionByType[$source['type']])){
                $extension = $extensionByType[$source['type']];
            }else{
                $this->errors[] = 'Error: Http posted image must be a jpeg or png to use this class.';
                return $this;
            }
        }
        elseif(is_resource($source) && get_resource_type($source) == 'gd'){
            $this->originalImage = $source;
        }
        elseif(file_exists($source)){
            $this->originalImagePath = $source;
            $this->originalImageFileInfos = pathinfo($this->originalImagePath); //d($fInfos); exit();
            $extension = strtolower($this->originalImageFileInfos['extension']);
            if( !in_array($extension, array('jpeg','jpg', 'png')) ){
                $this->errors[] = 'Error: Image must be a jpeg or png to use this class.';
                return $this;
            }
        }else{
            $this->errors[] = 'Error: Invalide source. Source must be either an image filepath or an http posted image file.';
            return $this;
        }
        if($extension) $this->originalImage = ($extension == 'png') ? imagecreatefrompng($this->originalImagePath) : ImageCreateFromJpeg($this->originalImagePath);
        //$originalImageSize = getimagesize($this->originalImagePath); //d($this->originalImageSize, 'originalImageSize'); //exit();
        $this->originalImageWidth = imagesx($this->originalImage); //$originalImageSize[0];
        $this->originalImageHeight = imagesy($this->originalImage); //$originalImageSize[1];
        $this->originalImageRatio = $this->originalImageWidth / $this->originalImageHeight; //d($this->originalImageRatio, 'originalImageRatio');
        return $this;
    }

    public function getTmpImageSrcAsBlob(){
        if(!empty($this->errors)){ return false; }
        if(empty($this->tmpImage)){
            $this->buildTmpImage();
        }
        if(!empty($this->errors)){ return false; }
        return self::getImageSrcAsBlob($this->tmpImage);
    }

    public function getFinalImageSrcAsBlob(){
        if(!empty($this->errors)){ return false; }
        if(empty($this->finalImage)){
            $this->buildFinalImage();
        }
        if(!empty($this->errors)){ return false; }
        return self::getImageSrcAsBlob($this->finalImage);
    }

    public function getOriginalImageSrcAsBlob(){
        return self::getImageSrcAsBlob($this->originalImage);
    }
    public static function getImageSrcAsBlob($gdImage){
        ob_start();
        imagepng($gdImage);
        $imageBlob = ob_get_contents(); // read from buffer
        ob_end_clean(); // delete buffer
        return 'data:image/png;base64,'.base64_encode($imageBlob).'';
    }

    public function buildFinalImage(){

        if(!empty($this->errors)){ return $this; }

        if(empty($this->heaviestZoneStartX) && empty($this->heaviestZoneStartY)){
            $this->findVariationsHeaviestZone();
        }

        $startX = 0;
        $startY = 0;

        if($this->heaviestZoneStartX !== false)
        {
            $height = $this->originalImageHeight;
            $width = $height * $this->finalImageRatio;
            $startX = round($this->heaviestZoneStartX * $this->originalImageWidth / $this->tmpImageWidth );
        }
        elseif($this->heaviestZoneStartY !== false)
        {
            $width = $this->originalImageWidth;
            $height = $width / $this->finalImageRatio;
            $startY = round(    $this->heaviestZoneStartY * $this->originalImageHeight / $this->tmpImageHeight     );
        }
        else{
            $this->errors[] = 'Error : No heaviest zone found.';
            return $this;
        }

        $this->finalImage = imagecreatetruecolor($width, $height);

        imagecopy(
            $this->finalImage,     //resource $dst_im - the image object ,
            $this->originalImage,  // resource $src_im - destination image ,
            0,                     // x coordinate in the destination image (use 0) , 
            0,                     // y coordinate in the destination image (use 0) , 
            $startX,               // x coordinate in the source image you want to crop , 
            $startY,               // y coordinate in the source image you want to crop , 
            $width,                // crop width ,
            $height                // crop height 
        );

        return $this;
    }

    public function findVariationsHeaviestZone(){

        if(!empty($this->errors)){ return $this; }

        $matrix = $this->getTmpImageVariationsMatrix();

        if(!$this->originalImageRatio > $this->finalImageRatio){
            $this->errors[] = 'Error : not concerned';
            return $this;
        }

        if( $this->finalImageRatio < $this->originalImageRatio ){

            $width = $this->tmpImageHeight * $this->finalImageRatio; //d($finalImageWidth, '$finalImageWidth');
            $height = $this->tmpImageHeight; //d($finalImageHeight, '$finalImageHeight');

            $weightByX = array();
            foreach ($matrix[0] as $x => $row) {

                $xMax = $x + $width;
                if( $xMax >= $this->tmpImageWidth) break;

                $weightByX[$x] = 0;

                for($subY=0; $subY < $height; $subY++){
                    for($subX=$x; $subX < $xMax; $subX++){
                        $weightByX[$x] += $matrix[$subY][$subX];
                    }
                }
            }
            arsort($weightByX); //d($weightByX, '$weightByX');
            reset($weightByX); 
            $this->heaviestZoneStartX = key($weightByX);
        }
        else{
            $width = $this->tmpImageWidth;
            $height = $this->tmpImageWidth  * $this->finalImageRatio;
            $weightByY = array();
            foreach($matrix as $y => $row) {
                $yMax = $y + $height;
                if( $yMax >= $this->tmpImageHeight) break;

                $weightByY[$y] = 0;

                for($subY=$y; $subY < $yMax; $subY++) { 
                    for($subX=1; $subX < $this->tmpImageWidth; $subX++){
                        $weightByY[$y] += $matrix[$subY][$subX];
                    }
                }
            }
            arsort($weightByY); //d($weightByY, '$weightByY');
            reset($weightByY); 
            $this->heaviestZoneStartY = key($weightByY);
        }
        return $this;
    }

    public function showVariationsHeaviestZoneOnTmpCopy(){
        if(!empty($this->errors)){ return $this; }

        if(!empty($this->heaviestZoneStartX))
            imagerectangle($this->tmpImage, $this->heaviestZoneStartX ,0 ,($this->heaviestZoneStartX + $this->finalImageWidth ), ($this->tmpImageHeight - 1), $this->flagColor);
        
        return $this;
    }

    public function saveVariationsHeaviestZonePortraitCropped($suffix = '_cropped'){
        $this->finalImagePath = $this->saveDir.'/'.$this->originalImageFileInfos['filename'].$suffix.'.png';
        if(is_file($this->finalImagePath)){
            unlink($this->finalImagePath); 
        }
        ImagePng($this->finalImage, $this->finalImagePath);
        chmod($this->finalImagePath, 0777);
        return $this;
    }

    public function buildTmpImage(){
        if(!empty($this->errors)){ return $this; }

        $this->tmpImageRatio = ($this->tmpImageWidth * 100) / $this->originalImageWidth; //d($this->tmpImageRatio, 'ratio');
        $this->tmpImageHeight = floor( ($this->originalImageHeight * $this->tmpImageRatio)/100 );
        
        $this->tmpImage = imagecreatetruecolor($this->tmpImageWidth, $this->tmpImageHeight);
        if(!$this->tmpImage){
            $this->errors[] = 'Error : buildTmpImage > imagecreatetruecolor for '.$this->filepath;
            return;
        }

        imagecopyresampled($this->tmpImage, $this->originalImage, 0, 0, 0, 0, $this->tmpImageWidth, $this->tmpImageHeight, $this->originalImageWidth, $this->originalImageHeight);

        if($this->useFilterGrayScale) imagefilter($this->tmpImage, IMG_FILTER_GRAYSCALE);
        if($this->useFilterContrast) imagefilter($this->tmpImage, IMG_FILTER_CONTRAST, $this->filterContrast);

        $this->flagColor = imagecolorallocate($this->tmpImage, 255, 0, 0);
        return $this;
    }

    public function saveTmpImage(){
        //$this->tmpImagePath = $this->originalImageFileInfos['dirname'].'/'.$this->originalImageFileInfos['filename'].'_'.$this->tmpCopySuffix.'.png';
        if(!empty($this->errors)){ return $this; }
        //header ("Content-type: image/png");ImagePng ($image);exit();
        if(is_file($this->tmpImagePath)){
            unlink($this->tmpImagePath); 
        }
        ImagePng($this->tmpImage, $this->tmpImagePath);
        chmod($this->tmpImagePath, 0777);
        return $this;
    }

    public function resetTmpImage(){
        $this->tmpImageMatrix = array();
        $this->tmpImageVariationsMatrix = array();
    }

    public function getTmpImageVariationsMatrix(){
        if(!empty($this->errors)){ return $this; }

        if(!empty($this->tmpImageVariationsMatrix)){
            return $this->tmpImageVariationsMatrix;
        }

        if(empty($this->tmpImage)){
            $this->buildTmpImage();
        }

        for ($y=0; $y < $this->tmpImageHeight; $y++) {

            $previousRgb = false;
            $reviousColors = array();

            for ($x=0; $x < $this->tmpImageWidth; $x++) {

                $rgb = imagecolorat($this->tmpImage, $x, $y);
                $colors = imagecolorsforindex($this->tmpImage, $rgb); //d($colors);exit;
                $this->tmpImageMatrix[$y][$x] = $colors;

                if($x){
                    $this->tmpImageVariationsMatrix[$y][$x] = self::getPixelColorsVariation($colors, $previousColors);
                }

                $previousRgb =$rgb;
                $previousColors = $colors;
            }
        }
        return $this->tmpImageVariationsMatrix;
    }

    public static function getPixelColorsVariation($colors, $previousColors){
        $variations = array();
        foreach ($colors as $name => $value) {
            if($previousColors[$name] != $value){
                $variations[$name] = ($previousColors[$name] > $value) ? $previousColors[$name] - $value : $value - $previousColors[$name];
            }else{
                $variations[$name] = 0;
            }
        }
        return array_sum($variations);
    }

    public function findVariationsCentroid(){
        if(!empty($this->errors)){ return $this; }
        $matrix = $this->getTmpImageVariationsMatrix();
        $this->tmpImageVariationsCentroid = self::getMatrixCentroid($matrix);
        return $this;
    }

    public function showVariationsCentroidOnTmpCopy(){
        if(!empty($this->errors)){ return $this; }
        imagefilledellipse($this->tmpImage, $this->tmpImageVariationsCentroid['x'], $this->tmpImageVariationsCentroid['y'], 2, 2, $this->flagColor);
        imagearc($this->tmpImage, $this->tmpImageVariationsCentroid['x'], $this->tmpImageVariationsCentroid['y'], 8, 8, 0, 360, $this->flagColor);
        return $this;
    }

    public static function getMatrixCentroid($matrix){
        $totalMass = 0;
        $totalX = 0;
        $totalY = 0;
        foreach ($matrix as $y => $row) {
            foreach ($row as $x => $value) {
                $totalMass += $value;
                $totalX += $x * $value;
                $totalY += $y * $value;
            }
        }
        return array('x' => $totalX/$totalMass, 'y' => $totalY/$totalMass);
    }

    public function getVariationsMatrix(){
        $matrix = $this->getTmpImageVariationsMatrix();
        return self::matrixToString($matrix);
    }

    public static function matrixToString($matrix){
        if(empty($matrix)) return '';
        $str = '<pre style="font-size:9px;line-height:15px;">';
        foreach ($matrix as $y => $row) {
            if(empty($row)) break;
            $str .= '<div>';
            foreach($row as $x => $value) {
                $str .= '<span>'.sprintf("%4s", $value).'</span>';
            }
            $str .= '</div>';
        }
        $str .= '</pre>';
        return $str;
    }

    public function cleanExit(){
        if(!empty($this->originalImage)) imagedestroy($this->originalImage);
        if(!empty($this->tmpImage)) imagedestroy($this->tmpImage);
        return $this;
    }

    // GET SET

    public function getFinalImageRatio(){
        return $this->finalImageRatio;
    }
    public function setFinalImageRatio($finalImageRatio){
        if(!empty($this->finalImage)){ // zoom case
            return self::instance($this->finalImage)->setFinalImageRatio($finalImageRatio);
        }
        $this->finalImageRatio = $finalImageRatio;
        return $this;
    }

    public function getTmpImageWidth(){
        return $this->tmpImageWidth;
    }
    public function setTmpImageWidth($tmpImageWidth){
        $this->tmpImageWidth = $tmpImageWidth;
        return $this;
    }

    public function getFilterContrast(){
        return $this->filterContrast;
    }
    public function setFilterContrast($filterContrast){
        $this->filterContrast = $filterContrast;
        return $this;
    }
}
