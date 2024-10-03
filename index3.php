<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minecraft Redstone Logic Circuit Simulation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            text-align: center;
        }

        canvas {
            background-color: #fff;
            border: 1px solid #ddd;
            margin-top: 20px;
            display: block;
            margin: 0 auto;
        }

        .instructions {
            margin-bottom: 20px;
        }

        button, input[type="button"] {
            padding: 10px 15px;
            margin: 10px;
            border: none;
            background-color: #3498db;
            color: #fff;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover, input[type="button"]:hover {
            background-color: #2980b9;
        }

        select {
            padding: 10px;
            margin: 10px;
        }

        #controls {
            margin-bottom: 20px;
        }

        .gate-button {
            display: inline-block;
            margin: 5px;
        }
    </style>
</head>
<body>
    <h1>Minecraft Redstone Logic Circuit Builder</h1>
    <p class="instructions">Click on gates to toggle their states. Create connections between gates by choosing a starting and ending gate in the dropdown menus. You can also add new gates using the buttons below.</p>
    <div id="controls">
        <select id="startGate">
            <option value="">Select Start Gate</option>
        </select>
        <select id="endGate">
            <option value="">Select End Gate</option>
        </select>
        <input type="button" value="Create Wire" onclick="createWire()" />
        <button onclick="resetCircuit()">Reset Circuit</button>
        <div id="gate-buttons" class="gate-button">
            <button onclick="setGateType('AND')">Add AND Gate</button>
            <button onclick="setGateType('OR')">Add OR Gate</button>
            <button onclick="setGateType('NOT')">Add NOT Gate</button>
            <button onclick="setGateType('XOR')">Add XOR Gate</button>
            <button onclick="setGateType('NAND')">Add NAND Gate</button>
            <button onclick="setGateType('NOR')">Add NOR Gate</button>
            <button onclick="setGateType('XNOR')">Add XNOR Gate</button>
            <button onclick="setGateType('BUFFER')">Add BUFFER Gate</button>
            <button onclick="setGateType('INPUT')">Add INPUT Gate</button>
        </div>
    </div>
    <canvas id="circuitCanvas" width="800" height="600"></canvas>
    <script>
        // Define the canvas and context
        const canvas = document.getElementById('circuitCanvas');
        const ctx = canvas.getContext('2d');

        // Define the grid size and snapping logic
        const gridSize = 50;
        const gates = [];
        const wires = [];
        let gateIdCounter = 1;
        let selectedGateType = 'AND';

        // Gate class definition with inputs, output, and type
        class Gate {
            constructor(x, y, type) {
                this.id = gateIdCounter++;
                this.x = x;
                this.y = y;
                this.type = type;
                this.input1 = false;
                this.input2 = type === 'NOT' || type === 'INPUT' ? null : false;
                this.output = false;
                this.state = false;
            }

            updateOutput() {
                switch (this.type) {
                    case 'AND':
                        this.output = this.input1 && this.input2;
                        break;
                    case 'OR':
                        this.output = this.input1 || this.input2;
                        break;
                    case 'NOT':
                        this.output = !this.input1;
                        break;
                    case 'XOR':
                        this.output = this.input1 !== this.input2;
                        break;
                    case 'NAND':
                        this.output = !(this.input1 && this.input2);
                        break;
                    case 'NOR':
                        this.output = !(this.input1 || this.input2);
                        break;
                    case 'XNOR':
                        this.output = this.input1 === this.input2;
                        break;
                    case 'BUFFER':
                        this.output = this.input1;
                        break;
                    case 'INPUT':
                        this.output = this.input1;  // INPUT gate acts as a switch
                        break;
                }
                this.state = this.output;
            }

            draw() {
                ctx.fillStyle = this.state ? '#e74c3c' : '#3498db';
                ctx.fillRect(this.x, this.y, gridSize, gridSize);
                ctx.fillStyle = '#fff';
                ctx.font = '16px Arial';
                ctx.fillText(this.type, this.x + 10, this.y + 25);
                ctx.fillText(this.output ? 'ON' : 'OFF', this.x + 15, this.y + 45);
            }

            toggleInput() {
                if (this.type === 'INPUT') {
                    this.input1 = !this.input1;
                    this.updateOutput();
                }
            }
        }

        // Wire class for connections between gates
        class Wire {
            constructor(from, to) {
                this.from = from;
                this.to = to;
                this.active = false;
            }

            draw() {
                const fromGate = gates.find(gate => gate.id === this.from);
                const toGate = gates.find(gate => gate.id === this.to);

                if (fromGate && toGate) {
                    ctx.beginPath();
                    ctx.moveTo(fromGate.x + gridSize / 2, fromGate.y + gridSize / 2);
                    ctx.lineTo(toGate.x + gridSize / 2, toGate.y + gridSize / 2);
                    ctx.strokeStyle = fromGate.output ? '#27ae60' : '#7f8c8d';  // Green if active, gray if not
                    ctx.lineWidth = 4;
                    ctx.stroke();
                }
            }

            updateState() {
                const fromGate = gates.find(gate => gate.id === this.from);
                if (fromGate) {
                    this.active = fromGate.output;
                    const toGate = gates.find(gate => gate.id === this.to);
                    if (toGate) {
                        // Determine which input to update
                        if (toGate.input1 === false || toGate.type === 'NOT' || toGate.type === 'BUFFER' || toGate.type === 'INPUT') {
                            toGate.input1 = this.active; // Set input1 if it's a single-input gate or not connected yet
                        } else if (toGate.input2 === false) {
                            toGate.input2 = this.active; // Set input2 if available
                        }
                        toGate.updateOutput();
                    }
                }
            }
        }

        // Draw the grid
        function drawGrid() {
            ctx.strokeStyle = '#ddd';
            ctx.lineWidth = 1;
            for (let x = 0; x < canvas.width; x += gridSize) {
                for (let y = 0; y < canvas.height; y += gridSize) {
                    ctx.strokeRect(x, y, gridSize, gridSize);
                }
            }
        }

        // Initialize gate placement dropdowns
        function populateDropdowns() {
            const startDropdown = document.getElementById('startGate');
            const endDropdown = document.getElementById('endGate');
            startDropdown.innerHTML = '<option value="">Select Start Gate</option>';
            endDropdown.innerHTML = '<option value="">Select End Gate</option>';

            gates.forEach(gate => {
                const option1 = document.createElement('option');
                const option2 = document.createElement('option');
                option1.value = gate.id;
                option1.text = `Gate ${gate.id} (${gate.type})`;
                option2.value = gate.id;
                option2.text = `Gate ${gate.id} (${gate.type})`;
                startDropdown.appendChild(option1);
                endDropdown.appendChild(option2);
            });
        }

        // Set the gate type to place when a button is clicked
        function setGateType(type) {
            selectedGateType = type;
        }

        // Draw the gates and wires on the canvas
        function renderCircuit() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            drawGrid();

            wires.forEach(wire => wire.draw());
            gates.forEach(gate => gate.draw());
        }

        // Update gate outputs based on their inputs
        function updateGates() {
            gates.forEach(gate => gate.updateOutput());
            wires.forEach(wire => wire.updateState());
        }

        // Handle gate click events to toggle inputs and update circuit
        canvas.addEventListener('click', function (event) {
            const clickX = Math.floor(event.offsetX / gridSize) * gridSize;
            const clickY = Math.floor(event.offsetY / gridSize) * gridSize;

            const clickedGate = gates.find(gate => gate.x === clickX && gate.y === clickY);
            if (clickedGate) {
                clickedGate.toggleInput();
                updateGates();
                renderCircuit();
            } else {
                gates.push(new Gate(clickX, clickY, selectedGateType));
                populateDropdowns();
                renderCircuit();
            }
        });

        // Create a new wire between selected gates
        function createWire() {
            const startGateId = parseInt(document.getElementById('startGate').value);
            const endGateId = parseInt(document.getElementById('endGate').value);

            if (startGateId && endGateId && startGateId !== endGateId) {
                wires.push(new Wire(startGateId, endGateId));
                updateGates();
                renderCircuit();
            } else {
                alert('Invalid wire connection. Please select different start and end gates.');
            }
        }

        // Reset the circuit to its initial state
        function resetCircuit() {
            gates.length = 0;
            wires.length = 0;
            gateIdCounter = 1;
            renderCircuit();
            populateDropdowns();
        }

        // Initial render and setup
        drawGrid();
        populateDropdowns();
    </script>
</body>
</html>
