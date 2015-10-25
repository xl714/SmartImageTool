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
<title>PHP-GD Smart Image Tool - Crop automatique "intelligent"</title>
<meta name="description" content="Classe détectant automatiquement la zone la plus intéressante dans une image par exemple pour la cropper. This class automatically find the most interesting zone in a picture in order to crop it.">
<meta name="author" content="Xavier Langlois aka XL714">
<meta charset="UTF-8">
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <h1>PHP-GD Smart Image Tool - Crop automatique "intelligent"</h1>
    <h4>Source code on github : <a target="_blank" href="https://github.com/xl714/SmartImageTool">https://github.com/xl714/SmartImageTool</a> © Xavier Langlois - Octobre 2015</h4>
    <p>FR: Classe php utilisant PHP-GD afin de détecter automatiquement la zone la plus intéressante dans une image pour la cropper. Elle utilise pour cela la variation de couleur entre chaque pixel.</p>
    <p>EN: This php class uses PHP-GD to automatically find the most interesting zone in a picture in order to crop it. It uses the color variations between each pixel.</p>

<pre class="code-conf"><span class="sh_symbol">&lt;?php</span>
    <span class="sh_comment">// You can change settings here</span>
    <span class="sh_preproc">include</span><span class="sh_symbol">(</span><span class="sh_string">'SmartImageTool.php'</span><span class="sh_symbol">);</span>
    <span class="sh_variable">$img</span> <span class="sh_symbol">=</span> SmartImageTool<span class="sh_symbol">::</span>instance<span class="sh_symbol">(</span> <span id="sourceDisplay" class="sh_string">'path/to/image.jpg'</span> <span class="sh_symbol">)</span>
            <span class="sh_symbol">-></span>setTmpImageWidth<span class="sh_symbol">(</span><select id="tmpImageWidth">
                    <option>10</option><option>20</option><option>30</option><option selected="selected">40</option><option>50</option><option>75</option><option>100</option><option>150</option><option>200</option>
                </select><span class="sh_symbol">)</span>
            <span class="sh_symbol">-></span>setFinalImageRatio<span class="sh_symbol">(</span><select id="finalImageRatioWidth">
                    <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6</option><option>7</option><option>8</option><option>9</option><option>10</option>
                </select> / <select id="finalImageRatioHeight">
                    <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6</option><option>7</option><option>8</option><option>9</option><option>10</option>
                </select><span class="sh_symbol">)</span>
            <span class="sh_symbol">-></span>buildFinalImage<span class="sh_symbol">();</span>
<input type="checkbox" id="getOriginalImageSrcAsBlob" checked="checked" /> <span class="sh_keyword">echo</span> <span class="sh_string">'&lt;img src="'</span>.<span class="sh_variable">$img</span><span class="sh_symbol">-></span>getOriginalImageSrcAsBlob<span class="sh_symbol">()</span>.<span class="sh_string">'" &gt;'</span>;
<input type="checkbox" disabled="disabled" checked="checked" /> <span class="sh_keyword">echo</span> <span class="sh_string">'&lt;img src="'</span>.<span class="sh_variable">$img</span><span class="sh_symbol">-></span>getFinalImageSrcAsBlob<span class="sh_symbol">()</span>.<span class="sh_string">'" &gt;'</span>;
<input type="checkbox" id="getVariationsMatrix" checked="checked" checked="checked" /> <span class="sh_keyword">echo</span> <span class="sh_variable">$img</span><span class="sh_symbol">-></span>getVariationsMatrix<span class="sh_symbol">();</span>
<span class="sh_symbol">?&gt;</span>
</pre>

    <div id="images-test">
        <div>Click an image</div>
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
            upload($(this).attr('src'));
        });
        $("#drop-area").on('drop', function (e){
            $(this).css('background', '#D8F9D3');
            e.preventDefault();
            images = e.originalEvent.dataTransfer.files;
            upload(images[0]);
        });
    });

    function upload(userImage){

        formData = new FormData();
        formData.append('userImage', userImage);
        formData.append('finalImageRatioWidth', $("#finalImageRatioWidth").val());
        formData.append('finalImageRatioHeight', $("#finalImageRatioHeight").val());
        formData.append('tmpImageWidth', $("#tmpImageWidth").val());
        formData.append('getOriginalImageSrcAsBlob', $('#getOriginalImageSrcAsBlob').prop('checked'));
        formData.append('getVariationsMatrix', $('#getVariationsMatrix').prop('checked'));

        source = (typeof userImage == 'string') ? userImage : userImage.name;
        $("#sourceDisplay").html("'"+source+"'");
        
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