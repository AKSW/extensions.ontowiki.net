$(document).ready(function(){

    // -- prefill some text input fileds ------------------------------------ */
    
    $('input.prefill, .prefill input').each(function()
    {
    
        var prefill_string = $(this).attr('title');

        if ($(this).attr('value') == '') $(this).attr('value', prefill_string);
        
        $(this).focus(function(){
            if ($(this).attr('value') == prefill_string) $(this).attr('value', '');
        });
        
        $(this).blur(function(){
            if ($(this).attr('value') == '') $(this).attr('value', prefill_string);
        });
    });
    
});
