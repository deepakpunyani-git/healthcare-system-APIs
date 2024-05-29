# HealthcareSystemAPIs

## Overview
HealthcareSystemAPIs is a robust backend solution designed to support both mobile and web applications in the healthcare domain. It provides RESTful APIs for managing patient data, appointments, medical records, and other healthcare-related functionalities. The system ensures secure and efficient data handling, leveraging modern authentication methods like JWT.

## Features
- User authentication and authorization
- Patient management (CRUD operations)
- Appointment scheduling
- Medical record management
- Secure JWT-based authentication
- Integration with mobile and web clients

## Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Apache/Nginx web server

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/deepakpunyani-git/healthcare-system-APIs
   cd HealthcareSystemAPIs


API Endpoints
Authentication
POST /api/register: Register a new user
POST /api/login: Login a user and retrieve JWT
Patients
GET /api/patients: Retrieve a list of patients
POST /api/patients: Create a new patient
GET /api/patients/{id}: Retrieve a specific patient
PUT /api/patients/{id}: Update a specific patient
DELETE /api/patients/{id}: Delete a specific patient
Appointments
GET /api/appointments: Retrieve a list of appointments
POST /api/appointments: Create a new appointment
GET /api/appointments/{id}: Retrieve a specific appointment
PUT /api/appointments/{id}: Update a specific appointment
DELETE /api/appointments/{id}: Delete a specific appointment
Medical Records
GET /api/medical_records: Retrieve a list of medical records
POST /api/medical_records: Create a new medical record
GET /api/medical_records/{id}: Retrieve a specific medical record
PUT /api/medical_records/{id}: Update a specific medical record
DELETE /api/medical_records/{id}: Delete a specific medical record



## Testing

### Prerequisites

- Postman or any other API testing tool

### Test Cases

https://docs.google.com/document/d/1X6ZiZbRK2Gb0eG1u4Z8gCUnv7pbcpvhNjt6_LZDUHHs/edit

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request.

## License

This project is licensed under the MIT License.