
function getFileSize(url)
{
	var request;
	  request = $.ajax({
		type: "HEAD",
		url: url,
		error: function () {
		  alert("Size is " + request.getResponseHeader("Content-Length"));
		}
	  });
	  
}