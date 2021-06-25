jQuery('.acf-icon.dark[data-name=edit]').click(function(){
setTimeout(() => {    
    jQuery('.acf-media-modal.-edit .media-sidebar .attachment-details .setting input[type=text]').attr('required','required');
  if(jQuery('.acf-media-modal.-edit .media-sidebar .attachment-details .setting[data-setting=alt] input[type=text]').val()==''){
     jQuery('.media-modal-content .media-toolbar-primary .media-button').attr('disabled','disabled');    
     jQuery('.acf-media-modal.-edit .media-sidebar .attachment-details .setting[data-setting=alt] input[type=text]').css('border','solid 1px red');     
    }
     jQuery('.acf-media-modal.-edit .media-sidebar .attachment-details .setting input[type=text]').keyup(function(){
        if(jQuery(this).val()!=''){
            jQuery(this).css('border','solid 1px #ddd');
            jQuery('.media-modal-content .media-toolbar-primary .media-button').removeAttr('disabled');   
        }else{
            jQuery(this).css('border','solid 1px red');
            jQuery('.media-modal-content .media-toolbar-primary .media-button').attr('disabled','disabled');
        }
    });    
}, 2000);    
});

