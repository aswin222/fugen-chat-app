<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fugen Chat App - Register</title>
    <!-- Include Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 dark:bg-gray-900">
    <section class="flex items-center justify-center h-screen px-6 py-8 mx-auto">
        <div class="w-full bg-white rounded-lg shadow md:max-w-md dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                <h1 class="text-xl font-medium leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white text-center">
                    Create an Account
                </h1>
                <div id="errorContainer" class="hidden bg-red-100 text-red-800 p-3 rounded">
                    <ul id="errorList"></ul>
                </div>
                
                <div id="successMessage" class="hidden bg-green-100 text-green-800 p-3 rounded"></div>
                
                <form id="registerForm" class="space-y-4 md:space-y-6">
                    <div>
                        <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your Name</label>
                        <input type="text" name="name" id="name" class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-primary-600 focus:border-primary-600 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="John Doe" required>
                    </div>
                    <div>
                        <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your Email</label>
                        <input type="email" name="email" id="email" class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-primary-600 focus:border-primary-600 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="name@mediezy.com" required>
                    </div>
                    <div>
                        <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password</label>
                        <input type="password" name="password" id="password" class="w-full px-4 py-2.5 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-primary-600 focus:border-primary-600 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="w-full px-4 py-2.5 text-sm font-medium text-white bg-black rounded-lg focus:ring-4 focus:outline-none focus:ring-primary-300 hover:bg-primary-700 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800" style="background-color: rgb(56,212,172)">Register</button>
                </form>
            </div>
        </div>
    </section>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function (e) {
            e.preventDefault();
    
            let name = document.getElementById('name').value;
            let email = document.getElementById('email').value;
            let password = document.getElementById('password').value;
            let errorContainer = document.getElementById('errorContainer');
            let errorList = document.getElementById('errorList');
            let successMessage = document.getElementById('successMessage');
    
            errorList.innerHTML = "";
            errorContainer.classList.add('hidden');
            successMessage.classList.add('hidden');
    
            fetch('api/register', { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: name, email: email, password: password })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMessage.textContent = "Registration successful! Redirecting...";
                    successMessage.classList.remove('hidden');
    
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 2000);
                } else {
                    errorContainer.classList.remove('hidden');
    
                    if (data.errors) {
                        Object.values(data.errors).forEach(errors => {
                            errors.forEach(error => {
                                let li = document.createElement('li');
                                li.textContent = error;
                                errorList.appendChild(li);
                            });
                        });
                    } else if (data.message) {
                        let li = document.createElement('li');
                        li.textContent = data.message;
                        errorList.appendChild(li);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                errorContainer.classList.remove('hidden');
                errorList.innerHTML = "<li>Something went wrong. Please try again.</li>";
            });
        });
    </script>
    
</body>

</html>