<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('SmartImageTool.php');

function d($var, $name = false){echo sd($var, $name);}
function sd($var, $name = false){return '<pre>'.( ($name)?$name.' = ':'' ).print_r($var,true).'</pre>';}

$source = false;
if( is_array( $_FILES ) && isset( $_FILES['userImage'] ) && is_uploaded_file( $_FILES['userImage']['tmp_name'] ) )
{
    $source = $_FILES['userImage'];
}
elseif( isset( $_POST['userImage'] ) && file_exists( 'images-test/'.basename( $_POST['userImage'] ) ) )
{
    $source = 'images-test/'.basename( $_POST['userImage'] );
}

if( $source )
{
    $finalImageRatio = 1;
    if (!empty($_POST['finalImageRatioWidth']) && !empty($_POST['finalImageRatioHeight'])) {
        $finalImageRatio = $_POST['finalImageRatioWidth'] / $_POST['finalImageRatioHeight'];
    }
    $tmpImageWidth = 40;
    if (!empty($_POST['tmpImageWidth'])) {
        $tmpImageWidth = (int) $_POST['tmpImageWidth'];
    }

    $filterContrast = -200;
    if (!empty($_POST['filterContrast'])) {
        $filterContrast = (int) $_POST['filterContrast'];
    }
    $img = SmartImageTool::instance( $source )->setTmpImageWidth($tmpImageWidth)
										      ->setFilterContrast($filterContrast)
										      ->setFinalImageRatio($finalImageRatio)
										      ->buildFinalImage();

    if(!empty($_POST['setFinalImageRatio2']) && $_POST['setFinalImageRatio2'] == 'true'){

        $finalImageRatio2 = $_POST['finalImageRatioWidth2'] / $_POST['finalImageRatioHeight2'];
        $img = $img->setFinalImageRatio( $finalImageRatio2 )->buildFinalImage();
    }
    if( empty( $img->errors ) )
    {
    	echo '<div class="row">';
    	echo '	<div class="col-xs-6">';
        if (!empty($_POST['getTmpImageSrcAsBlob']) && $_POST['getTmpImageSrcAsBlob'] == 'true')
        {
            $img->showVariationsHeaviestZoneOnTmpCopy();
            echo '		<img src="'.$img->getTmpImageSrcAsBlob().'" width="300" hspace="15" border="1"/>';
        }
        echo '	</div>';
        echo '	<div class="col-xs-6">';
        echo '		<img src="'.$img->getFinalImageSrcAsBlob().'" width="300" hspace="15" border="1">';

        echo '	</div>';
        echo '</div>';
        echo '<div class="row">';
        echo '	<div class="col-xs-12 image-matrix">';
        if (!empty($_POST['getVariationsMatrix']) && $_POST['getVariationsMatrix'] == 'true')
        {
            echo '<hr/>Matrice des variations de couleurs<br/>';
	        echo $img->getVariationsMatrixHtml();
	    }
        if (!empty($_POST['getContourMatrix']) && $_POST['getContourMatrix'] == 'true')
        {
            echo '<hr/>Matrice de contour (travail en cours)<br/>';
            echo $img->getContourMatrixHtml();
        }
	    echo '	</div>';
        echo '</div>';
    }
    else
    {
        echo 'Errors : <ul class="errors"><li>'.implode('</li><li>', $img->errors).'</li></ul><hr/>';
    }
    
    exit();
}
?>
<html>
<title>PHP-GD Smart Image Tool - Crop automatique "intelligent"</title>
<meta name="description" content="Classe détectant automatiquement la zone la plus intéressante dans une image par exemple pour la cropper. This class automatically find the most interesting zone in a picture in order to crop it.">
<meta name="author" content="Xavier Langlois aka XL714">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta charset="UTF-8">
<head>
    <script src="javascript/jquery.min.js"></script>
    <script src="javascript/bootstrap.min.js"></script>
    <script src="javascript/imagecrop.js"></script>
    <link rel="stylesheet" type="text/css" href="css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <h1>PHP-GD Smart Image Tool - Crop automatique "intelligent"</h1>
    <h4>Source code on github : <a target="_blank" href="https://github.com/xl714/SmartImageTool">https://github.com/xl714/SmartImageTool</a> © Xavier Langlois - Octobre 2015</h4>
    <p>FR: Classe php utilisant PHP-GD afin de détecter automatiquement la zone la plus intéressante dans une image pour la cropper. Elle utilise pour cela la variation de couleur entre chaque pixel.</p>
    <p>EN: This php class uses PHP-GD to automatically find the most interesting zone in a picture in order to crop it. It uses the color variations between each pixel.</p>

    
    <div class="row container-fluid">
    	<div class="col-xs-6">
	    	<form id="image-options-form" action="#" method="POST">
	    		<legend>Configuration</legend>

                <h6>Options de la copie de travail temporaire de l'image</h6>
				<div class="form-group">
					<div class="row">
						<div class="right-label col-xs-6">
							<label>Largeur :</label>
						</div>
						<div class="col-xs-4">
							<select id="TmpImageWidth" class="form-control" name="TmpImageWidth">
								<option>10</option>
								<option>20</option>
								<option>30</option>
								<option>40</option>
								<option>50</option>
								<option>75</option>
								<option>100</option>
				                <option>150</option>
				                <option>200</option>
				                <option>250</option>
				                <option>300</option>
				                <option>400</option>
				                <option>500</option>
				                <option>600</option>
							</select>
						</div>
						<div class="left-label col-xs-2">
							pixels
						</div>
					</div>
				</div>
				
				<div class="form-group">
					<div class="row">
						<div class="right-label col-xs-6">
							<label>Contraste :</label>
						</div>
						<div class="col-xs-4">
							<select id="TmpImageContrast" class="form-control" name="TmpImageContrast">
								<option>-0</option>
								<option>-50</option>
								<option>-100</option>
								<option>-150</option>
								<option selected="selected">-200</option>
								<option>-250</option>
								<option>-300</option>
				                <option>-400</option>
				                <option>-500</option>
							</select>
						</div>
						<div class="left-label col-xs-2"></div>
					</div>
				</div>

                <h6>Options de l'image finale</h6>
				
				<div class="form-group">
					<div class="row">
						<div class="right-label col-xs-6">
							<label>Ratio après premier traitement :</label>
						</div>
						<div class="col-xs-4">
							<div class="form-inline">
								<select id="TmpImageRatioWidth" class="form-control" name="TmpImageRatioWidth">
									<option>1</option>
									<option>2</option>
									<option>3</option>
									<option>4</option>
									<option>5</option>
									<option>6</option>
									<option>7</option>
					                <option>8</option>
					                <option>9</option>
					                <option>10</option>
								</select>
								/
								<select id="TmpImageRatioHeight" class="form-control" name="TmpImageRatioHeight">
									<option>1</option>
									<option>2</option>
									<option>3</option>
									<option>4</option>
									<option>5</option>
									<option>6</option>
									<option>7</option>
					                <option>8</option>
					                <option>9</option>
					                <option>10</option>
								</select>
							</div>
						</div>
						<div class="left-label col-xs-2"></div>
					</div>
				</div>
				
				<div class="form-group">
					<div class="row">
						<div class="right-label col-xs-6">
							<label>Retraiter l'image avec un 2ème ratio (= ZOOM):</label>
						</div>
						<div class="col-xs-6">
							<div class="checkbox">
								<label>
									<input id="UseSecondImageRatio" type="checkbox" value="1" name="UseSecondImageRatio" />
									<strong>Oui</strong>
								</label>
							</div>
						</div>
					</div>
				</div>

				<div id="UseSecondImageRatio-options" class="form-group">
					<div class="row">
						<div class="right-label col-xs-6">
							<label>Ratio pour 2ème traitement :</label>
						</div>
						<div class="col-xs-4">
							<div class="form-inline">
								<select id="TmpSecondImageRatioWidth" class="form-control" name="TmpSecondImageRatioWidth">
									<option>1</option>
									<option>2</option>
									<option>3</option>
									<option>4</option>
									<option>5</option>
									<option>6</option>
									<option>7</option>
					                <option>8</option>
					                <option>9</option>
					                <option>10</option>
								</select>
								/
								<select id="TmpSecondImageRatioHeight" class="form-control" name="TmpSecondImageRatioHeight">
									<option>1</option>
									<option>2</option>
									<option>3</option>
									<option>4</option>
									<option>5</option>
									<option>6</option>
									<option>7</option>
					                <option>8</option>
					                <option>9</option>
					                <option>10</option>
								</select>
							</div>
						</div>
						<div class="left-label col-xs-2"></div>
					</div>
				</div>
				
                <h6>Options d'affichage</h6>

				<div class="form-group">
					<div class="row">
						<div class="right-label col-xs-6">
							<label>Afficher de l'image de travail:</label>
						</div>
						<div class="col-xs-6">
							<div class="checkbox">
								<label>
									<input id="ShowWorkingImage" type="checkbox" value="1" name="ShowWorkingImage" />
									<strong>Oui</strong>
								</label>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="right-label col-xs-6">
							<label>Afficher de la matrice de travail:</label>
						</div>
						<div class="col-xs-6">
							<div class="checkbox">
								<label>
									<input id="ShowWorkingMatrice" type="checkbox" value="1" name="ShowWorkingMatrice" />
									<strong>Oui</strong>
								</label>
							</div>
						</div>
					</div>
                    <div class="row">
                        <div class="right-label col-xs-6">
                            <label>Afficher de la matrice de contour:</label>
                        </div>
                        <div class="col-xs-6">
                            <div class="checkbox">
                                <label>
                                    <input id="ShowContourMatrice" type="checkbox" value="1" name="ShowContourMatrice" />
                                    <strong>Oui</strong> <small>(Travail en cours...)</small>
                                </label>
                            </div>
                        </div>
                    </div>
				</div>
		    </form>
    	</div>
    	<div class="col-xs-6">
    		<legend>Code généré</legend>
    		<div id="generated-code" class="code-conf">
    			<span class="sh_symbol">&lt;?php</span><br/>
				    <span class="sh_preproc">include</span><span class="sh_symbol">(</span><span class="sh_string">'SmartImageTool.php'</span><span class="sh_symbol">);</span><br/>
				    <span class="sh_variable">$img</span> <span class="sh_symbol">=</span> SmartImageTool<span class="sh_symbol">::</span>instance<span class="sh_symbol">(</span> <span id="sourceDisplay" class="sh_string">'path/to/image.jpg'</span> <span class="sh_symbol">)</span><br/>
				        <span class="tab"></span><span class="sh_symbol">-></span>setTmpImageWidth<span class="sh_symbol">(</span><span id="TmpImageWidth-container"></span><span class="sh_symbol">)</span><br/>
				        <span class="tab"></span><span class="sh_symbol">-></span>setFilterContrast<span class="sh_symbol">(</span><span id="TmpImageContrast-container"></span><span class="sh_symbol">)</span><br/>
				        <span class="tab"></span><span class="sh_symbol">-></span>setFinalImageRatio<span class="sh_symbol">(</span><span id="TmpImageRatioWidth-container"></span> / <span id="TmpImageRatioHeight-container"></span><span class="sh_symbol">)</span><br/>
				    	<div id="UseSecondImageRatio-container">
				    		<span class="tab"></span><span class="sh_symbol">-></span>setFinalImageRatio<span class="sh_symbol">(</span><span id="TmpSecondImageRatioWidth-container"></span> / <span id="TmpSecondImageRatioHeight-container"></span><span class="sh_symbol">)</span><br/>
				        </div>
				        <span class="tab"></span><span class="sh_symbol">-></span>buildFinalImage<span class="sh_symbol">();</span><br/>
		        	<div id="ShowWorkingImage-container">
						<span class="sh_keyword">echo</span>&nbsp;&nbsp;&nbsp;<span class="sh_string">'&lt;img src="'</span>.<span class="sh_variable">$img</span><span class="sh_symbol">-></span>getTmpImageSrcAsBlob<span class="sh_symbol">()</span>.<span class="sh_string">'" &gt;'</span>;<br/>
					</div>
					<span class="sh_keyword">echo</span>&nbsp;&nbsp;&nbsp;<span class="sh_string">'&lt;img src="'</span>.<span class="sh_variable">$img</span><span class="sh_symbol">-></span>getFinalImageSrcAsBlob<span class="sh_symbol">()</span>.<span class="sh_string">'" &gt;'</span>;<br/>
					<div id="ShowWorkingMatrice-container">
						<span class="sh_keyword">echo</span>&nbsp;&nbsp;&nbsp;<span class="sh_variable">$img</span><span class="sh_symbol">-></span>getVariationsMatrixHtml<span class="sh_symbol">();</span><br/>
					</div>
                    <div id="ShowContourMatrice-container">
                        <span class="sh_keyword">echo</span>&nbsp;&nbsp;&nbsp;<span class="sh_variable">$img</span><span class="sh_symbol">-></span>getContourMatrixHtml<span class="sh_symbol">();</span><br/>
                    </div>
				<span class="sh_symbol">?&gt;</span>
			</div>
    	</div>
    </div>
    
    <div id="images-test" class="row">
    	<div class="col-xs-6">
	    	<legend>Cliquez sur une image</legend>
	    	<div class="row">
		    	<div class="col-xs-4">
		    		<img class="img-responsive" src="images-test/cat.jpg" />
		    	</div>
		    	<div class="col-xs-4">
		    		<img class="img-responsive" src="images-test/fox.jpg" />
		    	</div>
		    	<div class="col-xs-4">
		    		<img class="img-responsive" src="images-test/miranda-kerr-test-face.jpg" />
		    	</div>
		    </div>
	    </div>
	    <div class="col-xs-6">
	    	<legend>Ou sélectionnez la votre</legend>
		    <div id="drop-area">
		    	<input id="fileDragArea" type="file" name="" />
		        <div class="drop-text">
		        	<input id="fileDragArea" type="file" name="" />
		            Click to select or drag & drop an image here<br/>
		            It will be cropped with the input ratio and (hopefully) at the right place.
		        </div>
		    </div>
	    </div>
	</div>
    
    <div id="imagecrop-result">
	</div>

</body>
</html>