<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include('SmartImageTool.php');

function d($var, $name = false){echo sd($var, $name);}
function sd($var, $name = false){return '<pre>'.( ($name)?$name.' = ':'' ).print_r($var,true).'</pre>';}

if(is_array($_FILES) && isset($_FILES['userImage'])) {

    if(is_uploaded_file($_FILES['userImage']['tmp_name'])) {

        $img = SmartImageTool::instance( $_FILES['userImage'] )
            ->setFinalImageRatio(1)
            ->setTmpImageWidth(40)
            ->buildFinalImage();

        if(empty($img->errors)){
            echo '
                <img src="data:image/png;base64,'.base64_encode(file_get_contents($_FILES['userImage']['tmp_name'])).'" width="300" hspace="15" border="1"/>
                <h3>&nbsp;Result&#8680;&nbsp;</h3>
                <img src="'.$img->getFinalImageSrcAsBlob().'" width="150" hspace="15" border="1"> &nbsp; 
                <div style="clear:both;padding: 15px 0 0 0;">Variations matrix of the uploaded picture used to find the interesting zone:</div>';

            $img->printVariationsMatrix();
        }else{
            echo 'Errors : <ul><li>'.implode('</li><li>', $img->errors).'</li></ul><hr/>';
        }
        exit();
    }else{
        echo 'ERROR 1';
        print_r($_FILES);
        exit();
    }
}
?>
<html>
<title>Smart Image Tool</title>
<meta name="description" content="Classe détectant automatiquement la zone la plus intéressante dans une image par exemple pour la cropper. This class automatically find the most interesting zone in a picture in order to crop it.">
<meta name="author" content="Xavier Langlois aka XL714">
<meta charset="UTF-8">
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <style>
        #drop-area {
            width: 300px; height: 100px;
            position:absolute;
            left:50%; margin-left:-150px;
            border: 2px dashed rgba(0,0,0,.3);
            border-radius: 20px;
            font-family: Arial; text-align: center; 
            position: relative;
            line-height: 30px; font-size: 20px;
            color: rgba(0,0,0,.3);
        }
        #drop-area.hover { 
            border: 10px dashed #0c0 !important;
        }
        #result > *{
            float:left;
        }
        h1{text-align: center;}
        p{
            font-size: 12px;
            font-family: Arial;
        }
    </style>
</head>
<body>
    <h1>Smart Image Tool <sub>(<a target="_blank" href="https://github.com/xl714/SmartImageTool">code source on github</a>)</sub></h1>
    <h4>© Xavier Langlois - Octobre 2015</h4>
    <p>FR: Outil détectant automatiquement la zone la plus intéressante dans une image pour la cropper. Elle utilise pour cela la variation de couleur entre chaque pixel.</p>
    <p>EN: This tool automatically find the most interesting zone in a picture in order to crop it. It uses the color variations between each pixel.</p>

    <div id="drop-area">
        <div class="drop-text">
            Drag & Drop Image here<br/>
            It will be (hopefully) square cropped at the right place.
        </div>
    </div>
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

        $("#drop-area").on('drop', function (e){
            $(this).css('background', '#D8F9D3');
            e.preventDefault();
            var images = e.originalEvent.dataTransfer.files;
            console.log(images);
            var formData = new FormData();
            console.log(formData);
            formData.append('userImage', images[0]);
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
        });
    });

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