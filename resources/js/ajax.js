const ajax = (url, method = 'get', data = {}, domElement = null) => {
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
        let additionalFields = {...getCsrfFields()}

        if (method !== 'post') {
            options.method = 'post'
            additionalFields._METHOD = method.toUpperCase()
        }

        if (data instanceof FormData) {
            for (const additionalField in additionalFields) {
                data.append(additionalField, additionalFields[additionalField])
            }

            delete options.headers['Content-Type']

            options.body = data
        } else {
            options.body = JSON.stringify({...data, ...additionalFields})
        }
    } else if (method === 'get') {
        url += '?' + (new URLSearchParams(data)).toString()
    }

    return fetch(url, options).then(response => {
        if (domElement) {
            clearValidation(domElement)
        }

        if (! response.ok) {
            if (response.status === 422) {
                response.json().then(errors => {
                    handleErrors(errors, domElement)
                })
            } else if (response.status === 404) {
                alert(response.statusText)
            }
        }
        return response
    })
}

const get = (url, data) => ajax(url, 'get', data)
const post = (url, data, domElement) => ajax(url, 'post', data, domElement)
const del = (url, data) => ajax(url, 'delete', data)

function handleErrors(errors, domElement) {
    for (const name_error in errors) {
        const element = domElement.querySelector(`[name="${name_error}"]`)

        element.classList.add('is-invalid')

        const errorDiv = document.createElement('div')

        errorDiv.classList.add('invalid-feedback')
        errorDiv.textContent = errors[name_error][0]

        element.parentNode.append(errorDiv)
    }
}

function clearValidation(domElement) {
    domElement.querySelectorAll('.is-invalid').forEach(function(element) {
        element.classList.remove('is-invalid')

        element.parentNode.querySelectorAll('.invalid-feedback').forEach(function(event) {
            event.remove()
        })
    })
}

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
    post,
    del,
    clearValidation
}
