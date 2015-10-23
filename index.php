<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('SmartImageTool.php');

function d($var, $name = false){echo sd($var, $name);}
function sd($var, $name = false){return '<pre>'.( ($name)?$name.' = ':'' ).print_r($var,true).'</pre>';}

$source = false;

if(
    is_array($_FILES) && isset($_FILES['userImage'])
    && is_uploaded_file($_FILES['userImage']['tmp_name'])
) {
    $source = $_FILES['userImage'];
}elseif (isset($_POST['userImage']) && file_exists($_POST['userImage'])) {
    $source = $_POST['userImage'];
}

if($source){
    //d($_POST);

    $finalImageRatio = 1;
    if (!empty($_POST['finalImageRatioWidth']) && !empty($_POST['finalImageRatioHeight'])) {
        $finalImageRatio = $_POST['finalImageRatioWidth'] / $_POST['finalImageRatioHeight'];
    }

    $tmpImageWidth = 40;
    if (!empty($_POST['tmpImageWidth'])) {
        $tmpImageWidth = (int) $_POST['tmpImageWidth'];
    }

    $img = SmartImageTool::instance( $source )
        ->setFinalImageRatio($finalImageRatio)
        ->setTmpImageWidth($tmpImageWidth)
        ->buildFinalImage();

    if(empty($img->errors)){

        if (!empty($_POST['getOriginalImageSrcAsBlob']) && $_POST['getOriginalImageSrcAsBlob'] == 'true') {
            echo '<img src="'.$img->getOriginalImageSrcAsBlob().'" width="300" hspace="15" border="1"/>';
        }

        echo '<h3>&nbsp;Result&#8680;&nbsp;</h3>
            <img src="'.$img->getFinalImageSrcAsBlob().'" width="150" hspace="15" border="1">';

        if (!empty($_POST['getVariationsMatrix']) && $_POST['getVariationsMatrix'] == 'true') {
            echo '<div style="clear:both;padding: 15px 0 0 0;">Variations matrix of the uploaded picture used to find the interesting zone:</div>';
            echo $img->getVariationsMatrix();
        }

    }else{
        echo 'Errors : <ul><li>'.implode('</li><li>', $img->errors).'</li></ul><hr/>';
    }
    exit();
}
?>
<html>
<title>PHP-GD Smart Image Tool</title>
<meta name="description" content="Classe détectant automatiquement la zone la plus intéressante dans une image par exemple pour la cropper. This class automatically find the most interesting zone in a picture in order to crop it.">
<meta name="author" content="Xavier Langlois aka XL714">
<meta charset="UTF-8">
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <style>
        #drop-area {
            width: 500px; height: 140px;
            position:absolute;
            left:50%; margin-left:-250px; margin-top: 60px;
            border: 2px dashed gray;
            border-radius: 20px;
            font-family: Arial; text-align: center; 
            position: relative;
            line-height: 40px; font-size: 20px;
            color: gray;
            padding: 40px 20px 0px 20px;
        }
        #drop-area.hover { 
            border: 10px dashed #0c0 !important;
        }
        #result > *{
            float:left;
        }
        h1, h4{text-align: center;}
        p{
            font-size: 12px;
            font-family: Arial;
        }
        .code-conf{
            width:500px;
            float:left;
        }
        #images-test img{
            height:130px;
            margin-left: 10px;
            cursor: pointer;
        }
        #images-test div{
            font-size: 20px;
            font-family: Arial;
            color: gray;
        }
        .clear{ clear: both; width: 1px; height: 1px; padding: 0px; margin: 0; overflow: hidden; line-height: 1px;}
    </style>
</head>
<body>
    <h1>PHP-GD Smart Image Tool</h1>
    <h4>Source code on github : <a target="_blank" href="https://github.com/xl714/SmartImageTool">https://github.com/xl714/SmartImageTool</a> © Xavier Langlois - Octobre 2015</h4>
    <p>FR: Classe php utilisant PHP-GD afin de détecter automatiquement la zone la plus intéressante dans une image pour la cropper. Elle utilise pour cela la variation de couleur entre chaque pixel.</p>
    <p>EN: This php class uses PHP-GD to automatically find the most interesting zone in a picture in order to crop it. It uses the color variations between each pixel.</p>

<pre class="code-conf">&lt;?php

    include('SmartImageTool.php');

    $img = SmartImageTool::instance( <span id="sourceDisplay">"path/to/image.jpg"</span> )
            ->setFinalImageRatio(<select id="finalImageRatioWidth">
                    <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6</option><option>7</option><option>8</option><option>9</option><option>10</option>
                </select> / <select id="finalImageRatioHeight">
                    <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6</option><option>7</option><option>8</option><option>9</option><option>10</option>
                </select>)
            ->setTmpImageWidth(<select id="tmpImageWidth">
                    <option>10</option><option>20</option><option>30</option><option selected="selected">40</option><option>50</option><option>60</option><option>70</option><option>80</option><option>90</option><option>100</option>
                </select>)
            ->buildFinalImage();

<input type="checkbox" id="getOriginalImageSrcAsBlob" checked="checked" /> echo '&lt;img src="'.$img->getOriginalImageSrcAsBlob().'" &gt;';
<input type="checkbox" disabled="disabled" checked="checked" /> echo '&lt;img src="'.$img->getFinalImageSrcAsBlob().'" &gt;';
<input type="checkbox" id="getVariationsMatrix" checked="checked" checked="checked" /> echo $img->getVariationsMatrix();
</pre>

    <div id="images-test">
        <div>Click on a image</div>
        <img src="images-test/miranda-kerr.jpg">
        <img src="images-test/cat.jpg">
        <img src="images-test/fox.jpg">
    </div>
    
    <div id="drop-area">
        <div class="drop-text">
            Or drag & drop an image here<br/>
            It will be (hopefully) square cropped at the right place.
        </div>
    </div>
    <div class="clear"></div>
    <pre id="result"></pre>

    <script>
    $(document).ready(function() {
        $("#drop-area").on('dragenter', function (e){
            e.preventDefault();
            $(this).css('background', '#BBD5B8');
        });

        $("#drop-area").on('dragover', function (e){
            e.preventDefault();
        });

        $("#images-test img").on('click', function (e){
            e.preventDefault();
            var formData = new FormData();
            formData.append('userImage', $(this).attr('src'));
            upload(formData);
        });

        $("#drop-area").on('drop', function (e){
            $(this).css('background', '#D8F9D3');
            e.preventDefault();
            var images = e.originalEvent.dataTransfer.files;
            var formData = new FormData();
            formData.append('userImage', images[0]);
            upload(formData);
        });
    });

    function upload(formData){

        formData.append('finalImageRatioWidth', $("#finalImageRatioWidth").val());
        formData.append('finalImageRatioHeight', $("#finalImageRatioHeight").val());
        formData.append('tmpImageWidth', $("#tmpImageWidth").val());
        formData.append('getOriginalImageSrcAsBlob', $('#getOriginalImageSrcAsBlob').prop('checked'));
        formData.append('getVariationsMatrix', $('#getVariationsMatrix').prop('checked'));
        
        $.ajax({
            url: "index.php",
            type: "POST",
            data: formData,
            contentType:false,
            cache: false,
            processData: false,
            success: function(data){
                $('#result').html(data);
            }
        });
    }

    /*
    function readfiles(files) {
        for (var i = 0; i < files.length; i++) {
            document.getElementById('fileDragName').value = files[i].name
            document.getElementById('fileDragSize').value = files[i].size
            document.getElementById('fileDragType').value = files[i].type
            document.getElementById('fileDragData').value = files[i].slice();
            reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('fileDragData').value = event.target.result;
            }
            reader.readAsDataURL(files[i]);
        }
    }
    var holder = document.getElementById('holder');
    holder.ondragover = function () { this.className = 'hover'; return false; };
    holder.ondragend = function () { this.className = ''; return false; };
    holder.ondrop = function (e) {
        this.className = '';
        e.preventDefault();
        readfiles(e.dataTransfer.files);
    }
    */
    </script>
</body>
</html>