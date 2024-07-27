# IoTWebDashboardScript
 
IoT System Dashboard for Device Monitoring and Control

Key Features MQTT Private Broker: The system employs a private MQTT broker to facilitate secure and efficient communication between IoT devices and the server. This ensures real-time data transfer and control commands.

MariaDB Database: All sensor data and device statuses are stored in a MariaDB database. This relational database system provides robust and scalable data management, allowing for efficient querying and data retrieval.

Dashboard Overview: The main dashboard displays critical information, including current readings from potentiometers, temperature sensors, and humidity sensors. It also shows the status of various actuators, such as servos and lamp buttons.Users can view the current connection status of the server, ensuring that all devices are online and functioning correctly.

Sensor Data History: The "Data Sensor" section provides a detailed history of sensor readings, including timestamps, sensor names, and recorded values. This historical data is essential for analyzing trends and detecting anomalies.

Device Control: The system allows users to control actuators remotely. For example, users can adjust the position of a servo or toggle a lamp button on and off directly from the dashboard.

User Management: The "Data User" section offers user management capabilities, enabling administrators to add, edit, or remove users. This ensures that only authorized personnel can access and control the IoT system.

Device Status Monitoring: The "Data Device" section provides an overview of all connected devices, displaying their serial numbers, locations, and current statuses (online/offline). This helps in maintaining an organized and efficient IoT network.