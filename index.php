
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Graphique des Tâches</title>
    <link rel="stylesheet" href="/bulma/css/bulma.min.css">
    <link rel="stylesheet" href="/fontawesome/css/all.min.css">
	
    <script src="d3/d3.v7.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .nav-buttons {
            display: flex;
        }
        
        .nav-button {
            padding: 8px 16px;
            margin-left: 10px;
            border: 1px solid #ddd;
        }
        
        .nav-button.active {
            background-color: #000;
            color: white;
        }
        
        .content-section {
            display: none;
            margin-top: 20px;
        }
        
        .content-section.active {
            display: block;
        }
        
        .date-selector {
            margin-bottom: 20px;
        }
        
        .chart-container {
            overflow-x: auto;
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-top: 20px;
        }
        
        .tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.75);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            display: none;
            pointer-events: none; 
        }
        
        .axis-label {
            font-size: 12px;
        }
        
        /* Style pour le popup */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 8px;
            width: 60%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .task-list {
            margin-top: 15px;
            list-style-type: none;
            padding: 0;
        }
        
        .task-item {
            padding: 8px;
            margin-bottom: 5px;
            border-radius: 4px;
            background-color: #f9f9f9;
            border-left: 5px solid #ccc;
        }
        
        .task-status {
            float: right;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            color: white;
        }
        
        .status-success {
            background-color: #4CAF50;
        }
        
        .status-failed {
            background-color: #f44336;
        }
        
        .status-in-progress {
            background-color: #ff9800;
        }
        
        .server-list {
            list-style: none;
            padding: 0;
        }
        
        .server-item {
            padding: 10px;
            margin-bottom: 8px;
            background-color: #f9f9f9;
            border-radius: 4px;
            border-left: 5px solid #2196F3;
        }
        
        .server-status {
            float: right;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            color: white;
            background-color: #2196F3;
        }
    </style>
</head>
<body>
	<!-- Navbar -->
	<nav class="navbar is-primary" role="navigation" aria-label="main navigation">
		<div class="navbar-brand">
			<a class="navbar-item" href="#">
				<strong>Tour de guet - Surveillance des tâches planifiées</strong>
			</a>
			<a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="mainNavbar">
				<span aria-hidden="true"></span>
				<span aria-hidden="true"></span>
				<span aria-hidden="true"></span>
			</a>
		</div>
		<div id="mainNavbar" class="navbar-menu">
			<div class="navbar-start">
				<a class="navbar-item" href="taches.php"><i class="fas fa-tasks mr-1"></i>Tâches</a>
				<a class="navbar-item" href="serveurs.php"><i class="fas fa-server mr-1"></i>Serveurs</a>
				<a class="navbar-item is-active" href="index.php"><i class="fas fa-calendar-check mr-1"></i>État des planifications</a>
				<a class="navbar-item" href="config.php"><i class="fas fa-cog mr-1"></i>Configuration</a>
			</div>
		</div>
	</nav>
    
    <!-- Section Graph -->
    <div id="graphSection" class="content-section active">
        <div class="chart-container">
            <svg id="ganttChart"></svg>
        </div>
        <div id="tooltip" class="tooltip"></div>
    </div>
    

    <!-- Modal pour afficher les tâches -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3 id="modal-title">Détails des tâches</h3>
            <p id="modal-server"></p>
            <p id="modal-period"></p>
            <ul id="task-list" class="task-list"></ul>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const margin = { top: 50, right: 30, bottom: 50, left: 100 };
        const width = 3000 - margin.left - margin.right;
        const height = 6000 - margin.top - margin.bottom;

        const svg = d3.select("#ganttChart")
            .attr("width", width + margin.left + margin.right)
            .attr("height", height + margin.top + margin.bottom)
            .append("g")
            .attr("transform", `translate(${margin.left},${margin.top})`);

        const startDate = new Date("2024-07-02T00:00:00");
        const endDate = new Date("2024-07-09T23:00:00");

        const scaleFactor = 0.5;
        const x = d3.scaleTime()
            .domain([startDate, endDate])
            .range([0, width * scaleFactor]);

        const serveurs = Array.from({ length: 150 }, (_, i) => `Serveur ${i + 1}`);

        const y = d3.scaleBand()
            .domain(serveurs)
            .range([0, height])
            .padding(0);

        serveurs.forEach(serveur => {
            const yPos = y(serveur) + y.bandwidth() / 2;

            const buttonGroup = svg.append("g")
                .attr("transform", `translate(0, ${yPos})`)
                .attr("class", "server-button-group");

            buttonGroup.append("rect")
                .attr("x", -90)
                .attr("y", -12)
                .attr("width", 85)
                .attr("height", 24)
                .attr("fill", "#f0f0f0")
                .attr("stroke", "#ddd")
                .attr("rx", 4)
                .attr("ry", 4)
                .style("cursor", "pointer");

            buttonGroup.append("text")
                .attr("x", -45)
                .attr("y", 4)
                .attr("text-anchor", "middle")
                .text(serveur)
                .style("font-size", "12px")
                .style("pointer-events", "none");

            buttonGroup.on("click", function () {
                alert(`Serveur sélectionné: ${serveur}`);
            });

            buttonGroup.on("mouseover", function () {
                d3.select(this).select("rect").attr("fill", "#e0e0e0");
            }).on("mouseout", function () {
                d3.select(this).select("rect").attr("fill", "#f0f0f0");
            });
        });

        const xAxis = d3.axisBottom(x)
            .ticks(d3.timeHour.every(2))
            .tickFormat(d3.timeFormat("%H"));

        svg.append("g")
            .attr("transform", `translate(0,${height})`)
            .call(xAxis)
            .selectAll("text")
            .attr("transform", "rotate(-45)")
            .style("text-anchor", "end");

		const yAxis = d3.axisLeft(y)
			.tickFormat(() => "");


        svg.append("g").call(yAxis);

        const formatDate = d3.timeFormat("%d %b");
        svg.selectAll(".dayLabel")
            .data(d3.timeDays(startDate, endDate))
            .enter()
            .append("text")
            .attr("class", "dayLabel")
            .attr("x", d => x(d))
            .attr("y", height + 30)
            .attr("text-anchor", "middle")
            .text(d => formatDate(d))
            .style("font-size", "12px")
            .style("font-weight", "bold");
        // Définition des tâches avec leurs sous-tâches détaillées
        const tasks = [
            { 
                server: 9, 
                start: "2024-07-06T00:00:00", 
                end: "2024-07-06T05:00:00", 
                count: 4, 
                isFailed: false,
                subtasks: [
                    { id: 1, description: "Description de la tâche", status: "success" },
                    { id: 2, description: "Description de la tâche", status: "success" },
                    { id: 3, description: "Description de la tâche", status: "success" },
                    { id: 4, description: "Description de la tâche", status: "in-progress" }
                ]
            },
            { 
                server: 9, 
                start: "2024-07-06T06:00:00", 
                end: "2024-07-06T11:00:00", 
                count: 8, 
                isFailed: true,
                subtasks: [
                    { id: 1, description: "Description de la tâche", status: "success" },
                    { id: 2, description: "Description de la tâche", status: "failed" },
                    { id: 3, description: "Description de la tâche", status: "failed" },
                    { id: 4, description: "Description de la tâche", status: "success" },
                    { id: 5, description: "Description de la tâche", status: "success" },
                    { id: 6, description: "Description de la tâche", status: "in-progress" },
                    { id: 7, description: "Description de la tâche", status: "in-progress" },
                    { id: 8, description: "Description de la tâche", status: "success" }
                ]
            },
            { 
                server: 9, 
                start: "2024-07-06T12:00:00", 
                end: "2024-07-06T20:00:00", 
                count: 2, 
                isFailed: false,
                subtasks: [
                    { id: 1, description: "Description de la tâche", status: "in-progress" },
                    { id: 2, description: "Description de la tâche", status: "in-progress" }
                ]
            },
            { 
                server: 20, 
                start: "2024-07-04T01:00:00", 
                end: "2024-07-04T04:00:00", 
                count: 3, 
                isFailed: false,
                subtasks: [
                    { id: 1, description: "Description de la tâche", status: "success" },
                    { id: 2, description: "Description de la tâche", status: "success" },
                    { id: 3, description: "Description de la tâche", status: "success" }
                ]
            },
            { 
                server: 20,
                start: "2024-07-04T05:00:00",
                end: "2024-07-04T09:00:00",
                count: 5,
                isFailed: true,
                subtasks: [
                    { id: 1, description: "Description de la tâche", status: "success" },
                    { id: 2, description: "Description de la tâche", status: "success" },
                    { id: 3, description: "Description de la tâche", status: "failed" },
                    { id: 4, description: "Description de la tâche", status: "in-progress" },
                    { id: 5, description: "Description de la tâche", status: "success" }
                ]
            },
            { 
                server: 70, 
                start: "2024-07-06T00:00:00", 
                end: "2024-07-06T05:00:00", 
                count: 4, 
                isFailed: false,
                subtasks: [
                    { id: 1, description: "Description de la tâche", status: "success" },
                    { id: 2, description: "Description de la tâche", status: "success" },
                    { id: 3, description: "Description de la tâche", status: "in-progress" },
                    { id: 4, description: "Description de la tâche", status: "in-progress" }
                ]
            },
            { 
                server: 70, 
                start: "2024-07-06T06:00:00", 
                end: "2024-07-06T11:00:00", 
                count: 8, 
                isFailed: true,
                subtasks: [
                    { id: 1, description: "Description de la tâche", status: "success" },
                    { id: 2, description: "Description de la tâche", status: "failed" },
                    { id: 3, description: "Description de la tâche", status: "failed" },
                    { id: 4, description: "Description de la tâche", status: "success" },
                    { id: 5, description: "Description de la tâche", status: "success" },
                    { id: 6, description: "Description de la tâche", status: "failed" },
                    { id: 7, description: "Description de la tâche", status: "success" },
                    { id: 8, description: "Description de la tâche", status: "success" }
                ]
            },
            { 
                server: 70, 
                start: "2024-07-06T12:00:00", 
                end: "2024-07-06T20:00:00", 
                count: 2, 
                isFailed: false,
                subtasks: [
                    { id: 1, description: "Description de la tâche", status: "success" },
                    { id: 2, description: "Description de la tâche", status: "success" }
                ]
            },
            { 
                server: 50, 
                start: "2024-07-04T01:00:00", 
                end: "2024-07-04T04:00:00", 
                count: 3, 
                isFailed: false,
                subtasks: [
                    { id: 1, description: "Description de la tâche", status: "success" },
                    { id: 2, description: "Description de la tâche", status: "success" },
                    { id: 3, description: "Description de la tâche", status: "success" }
                ]
            },
            { 
                server: 50,
                start: "2024-07-04T05:00:00",
                end: "2024-07-04T09:00:00",
                count: 10,
                isFailed: true,
                subtasks: [
                    { id: 1, description: "Description de la tâche", status: "success" },
                    { id: 2, description: "Description de la tâche", status: "success" },
                    { id: 3, description: "Description de la tâche", status: "failed" },
                    { id: 4, description: "Description de la tâche", status: "success" },
                    { id: 5, description: "Description de la tâche", status: "success" },                 
                    { id: 6, description: "Description de la tâche", status: "success" },
                    { id: 7, description: "Description de la tâche", status: "success" },
                    { id: 8, description: "Description de la tâche", status: "failed" },
                    { id: 9, description: "Description de la tâche", status: "success" },
                    { id: 10, description: "Description de la tâche", status: "success" }
                ]
            },
        ];

        const tooltip = d3.select("#tooltip");
        const modal = document.getElementById("taskModal");
        const closeBtn = document.querySelector(".close");
        
        closeBtn.onclick = function() {
            modal.style.display = "none";
        };
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        };

        tasks.forEach((task, index) => {
            const taskStart = new Date(task.start);
            const taskEnd = new Date(task.end);
            const taskWidth = x(taskEnd) - x(taskStart);

            // Augmentation de la hauteur des barres pour mieux visualiser
            const taskHeight = Math.max(15, 30 + task.count * 5);

            const taskYPosition = y(`Serveur ${task.server}`) + (y.bandwidth() / 2) - (taskHeight / 2);

            const taskColor = task.isFailed ? "red" : "steelblue";

            svg.append("rect")
                .attr("x", x(taskStart))
                .attr("y", taskYPosition)
                .attr("width", taskWidth)
                .attr("height", taskHeight)
                .attr("fill", taskColor)
                .attr("rx", 5)
                .attr("ry", 5)
                .attr("cursor", "pointer")
                .on("mouseover", function (event) {
                    const startFormatted = d3.timeFormat("%d %b %H:%M")(taskStart);
                    const endFormatted = d3.timeFormat("%d %b %H:%M")(taskEnd);
                    tooltip.style("display", "block")
                           .html(`Serveur ${task.server}<br>De ${startFormatted} à ${endFormatted}<br>(${task.count} tâches)`);
                })
                .on("mousemove", function (event) {
                    tooltip.style("left", (event.pageX + 10) + "px")
                           .style("top", (event.pageY + 10) + "px");
                })
                .on("mouseout", function () {
                    tooltip.style("display", "none");
                })
                .on("click", function (event) {
                    const startFormatted = d3.timeFormat("%d %b %H:%M")(taskStart);
                    const endFormatted = d3.timeFormat("%d %b %H:%M")(taskEnd);
                    
                    document.getElementById("modal-title").textContent = `Détails des tâches`;
                    document.getElementById("modal-server").textContent = `Serveur ${task.server}`;
                    document.getElementById("modal-period").textContent = `De ${startFormatted} à ${endFormatted}`;
                    
                    const taskList = document.getElementById("task-list");
                    taskList.innerHTML = "";
                    
                    const subtasks = task.subtasks 
                    
                    subtasks.forEach(subtask => {
                        const li = document.createElement("li");
                        li.className = "task-item";
                        
                        const statusSpan = document.createElement("span");
                        statusSpan.className = `task-status status-${subtask.status}`;
						if (subtask.status == "success") {
							statusSpan.textContent = "Réussite";
						} else if (subtask.status == "failed") {
							statusSpan.textContent = "Échec";
						} else if (subtask.status == "in-progress") {
							statusSpan.textContent = "En cours";
						}

                        
                        li.innerHTML = `<strong>Tâche ${subtask.id}:</strong> ${subtask.description} `;
                        li.appendChild(statusSpan);
                        taskList.appendChild(li);
                    });
                    
                    modal.style.display = "block";
                });
        });
		document.getElementById('btnTasks').addEventListener('click', function() {
			window.location.href = 'index.php'; 
		});
		document.getElementById('btnTasks').addEventListener('click', function() {
			window.location.href = 'taches.php'; 
		});
		document.getElementById('btnServers').addEventListener('click', function() {
			window.location.href = 'serveurs.php'; 
		});

    });
    </script>
</body>
</html>