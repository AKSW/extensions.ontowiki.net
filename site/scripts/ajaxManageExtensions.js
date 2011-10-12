//handle the click on the "update" button that adds an extension
$(document).ready(function(){
    $("#updateExtensionButton").click(function(){
        var url = $("#updateExtensionInput").val()
        if(url != "" && url != $("#updateExtensionInput").attr("title")){
            $.post("reposerver/update",{"url": url }, function(data, status){
                if(data.ok){
                    alert("ok")
                }
            }, "json")
        }
    })
})