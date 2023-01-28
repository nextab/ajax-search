const nxt_afp_filter = async (event) => {
	// use document.querySelector(selector) to use css-selectors
	let filterElement = document.getElementById('filter');
	let responseElement = document.getElementById('response');

	// using js fetch-api to query data
	let result = await fetch(filterElement.getAttribute('action'), 
	{
		method: filterElement.getAttribute('method'), // POST
		body: new FormData(filterElement) // form data
	});

	let content = await result.text();

	// insert data
	responseElement.innerHTML = content;
}