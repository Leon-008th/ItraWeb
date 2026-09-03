# ItraWeb

ItraWeb is an educational movie-focused web application developed in PHP. The project is designed as a controlled-access application and is currently intended for development, testing, and educational purposes rather than public deployment.

The application uses **The Movie Database (TMDB) API** to search for and retrieve information about movies. When a movie is selected, ItraWeb uses the movie's TMDB ID to locate the corresponding video source and returns the result to the webpage for playback.

## Current Development Status

ItraWeb is still in the **early stages of development**. At the moment, only the homepage has been implemented. The homepage displays a selection of currently trending movies retrieved through the TMDB API.

Additional functions includes:

* User profiles
* Profile picture uploads through **Cloudinary**
* Customizable usernames and display names
* Password management
* User comments
* Comment reporting
* Administrative commands and controls
* User profile customization
* Controlled user account creation

## User System

ItraWeb includes a user-management system backed by **MySQL**. Users can customize various aspects of their profiles, including their username, name, password, profile picture, and other profile information.

Profile pictures are uploaded and managed through Cloudinary, while user and application data is stored using MySQL.

The application also includes administrative functionality for managing users, comments, reports, and other aspects of the website.

## Access & Deployment

ItraWeb is **not currently hosted on a public server**. Access is restricted to a controlled group of users, such as the owner, administrators, and authorized teachers.

Account creation is also restricted. Users cannot freely register; an account can only be created when the owner authorizes access and the required account information is present in the application's MySQL database.

This restricted setup is intended to keep the project within its educational and development environment while the application is still being built.

## Technologies

* **PHP** — Backend and application logic
* **MySQL** — User and application data
* **TMDB API** — Movie information and search
* **Cloudinary** — Profile picture storage
* **HTML / CSS / JavaScript** — Frontend
* **Composer** — PHP dependency management

The current version represents the foundation of ItraWeb. The homepage and core systems are being developed first, with additional pages and functionality planned for future versions.
