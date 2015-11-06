$(document).ready(function() {
	function updateCode(){
		$('#TmpImageWidth-container').html( $('#TmpImageWidth').val() );
		$('#TmpImageContrast-container').html( $('#TmpImageContrast').val() );
		$('#TmpImageRatioWidth-container').html( $('#TmpImageRatioWidth').val() );
		$('#TmpImageRatioHeight-container').html( $('#TmpImageRatioHeight').val() );
		if( $('#UseSecondImageRatio').is(':checked') )
		{
			$('#UseSecondImageRatio-container').show();
			$('#UseSecondImageRatio-options').show();
		}
		else
		{
			$('#UseSecondImageRatio-container').hide();
			$('#UseSecondImageRatio-options').hide();
		}
		$('#TmpSecondImageRatioWidth-container').html( $('#TmpSecondImageRatioWidth').val() );
		$('#TmpSecondImageRatioHeight-container').html( $('#TmpSecondImageRatioHeight').val() );
		
		if( $('#ShowWorkingImage').is(':checked') )
		{
			$('#ShowWorkingImage-container').show();
		}
		else
		{
			$('#ShowWorkingImage-container').hide();
		}

		if( $('#ShowWorkingMatrice').is(':checked') )
		{
			$('#ShowWorkingMatrice-container').show();
		}
		else
		{
			$('#ShowWorkingMatrice-container').hide();
		}

		if( $('#ShowContourMatrice').is(':checked') )
		{
			$('#ShowContourMatrice-container').show();
		}
		else
		{
			$('#ShowContourMatrice-container').hide();
		}
	};
	
	$(document).on( 'change', '#image-options-form .input, #image-options-form select, #image-options-form :checkbox', function(){
		updateCode();
	});
	updateCode();
	
	
	
	function upload( userImage ){
		
		formData = new FormData();
		formData.append('userImage', userImage);
		formData.append('tmpImageWidth', $("#TmpImageWidth").val());
	    formData.append('filterContrast', $("#TmpImageContrast").val());
		formData.append('finalImageRatioWidth', $("#TmpImageRatioWidth").val());
        formData.append('finalImageRatioHeight', $("#TmpImageRatioHeight").val());
        formData.append('setFinalImageRatio2', $('#UseSecondImageRatio').is(':checked') );
        formData.append('finalImageRatioWidth2', $("#TmpSecondImageRatioWidth").val());
        formData.append('finalImageRatioHeight2', $("#TmpSecondImageRatioHeight").val());
        formData.append('getOriginalImageSrcAsBlob', true );
        formData.append('getTmpImageSrcAsBlob', $('#ShowWorkingImage').is(':checked') );
        formData.append('getVariationsMatrix', $('#ShowWorkingMatrice').is(':checked') );
        formData.append('getContourMatrix', $('#ShowContourMatrice').is(':checked') );

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
                $('#imagecrop-result').html(data);
            }
        });
    }

	$("#images-test img").on('click', function (e){
        e.preventDefault();
        upload( $(this).attr('src') );
    });
	$(document.body).on( 'change', '#fileDragArea', function( e ){
        e.preventDefault();
        images = this.files;
	    upload( images[0] );
    });
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
	    images = e.originalEvent.dataTransfer.files;
	    upload(images[0]);
	});

});