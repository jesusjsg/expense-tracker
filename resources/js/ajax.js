const ajax = (url, method = 'get', data = {}) => {
    method = method.toLowerCase()

    let options = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    }

    const csrfMethods = new Set(['post', 'put', 'delete', 'patch'])

    if (csrfMethods.has(method)) {
        options.body = JSON.stringify({...data, ...getCsrfFields()})
    } else if (method === 'get') {
        url += '?' + (new URLSearchParams(data)).toString()
    }

    return fetch(url, options).then(response => response.json())
}

const get = (url, data) => ajax(url, 'get', data)
const post = (url, data) => ajax(url, 'post', data)

function getCsrfFields() {
    const csrfInputName = document.querySelector('#csrfName')
    const csrfInputValue = document.querySelector('#csrfValue')
    const csrfNameKey = csrfInputName.getAttribute('name')
    const csrfName = csrfInputName.content
    const csrfValueKey = csrfInputValue.getAttribute('name')
    const csrfValue = csrfInputValue.content

    return {
        [csrfNameKey]: csrfName,
        [csrfValueKey]: csrfValue
    }
}

export {
    ajax,
    get,
    post
}
