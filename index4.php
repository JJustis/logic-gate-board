<?php
session_start();
?>

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

        .file-controls {
            margin: 20px 0;
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
            <button onclick="toggleGateType('AND')">Toggle AND Gate</button>
            <button onclick="toggleGateType('OR')">Toggle OR Gate</button>
            <button onclick="toggleGateType('NOT')">Toggle NOT Gate</button>
            <button onclick="toggleGateType('XOR')">Toggle XOR Gate</button>
            <button onclick="toggleGateType('NAND')">Toggle NAND Gate</button>
            <button onclick="toggleGateType('NOR')">Toggle NOR Gate</button>
            <button onclick="toggleGateType('XNOR')">Toggle XNOR Gate</button>
            <button onclick="toggleGateType('BUFFER')">Toggle BUFFER Gate</button>
            <button onclick="toggleGateType('INPUT')">Toggle INPUT Gate</button>
            <button onclick="toggleGateType('CPU')">Toggle 4-bit CPU</button>
            <button onclick="toggleGateType('PSU')">Toggle PSU</button>
            <button onclick="toggleGateType('ENC')">Toggle Encryption Chip</button>
            <button onclick="toggleGateType('DEC')">Toggle Decryption Chip</button>
            <button onclick="toggleGateType('Clock')">Toggle Clock</button>
            <button onclick="toggleGateType('IO')">Toggle IO</button>
        </div>
    </div>
    <div class="file-controls">
        <button onclick="saveCircuit()">Save Circuit</button>
        <input type="file" id="fileInput" accept=".json" onchange="loadCircuit(event)" />
    </div>
    <canvas id="circuitCanvas" width="1000" height="800"></canvas>
    <script>
        const canvas = document.getElementById('circuitCanvas');
        const ctx = canvas.getContext('2d');

        const gridSize = 50;
        const gates = [];
        const wires = [];
        let gateIdCounter = 1;
        let selectedGateType = null;
        let draggedGate = null;
        let clockInterval = null;
        const psuKeys = {}; // Store PSU-generated keys for encryption and decryption

        class Gate {
            constructor(x, y, type, id = null) {
                this.id = id !== null ? id : gateIdCounter++;
                this.x = x;
                this.y = y;
                this.type = type;
                this.input1 = false;
                this.input2 = type === 'NOT' || type === 'INPUT' || type === 'CPU' ? null : false;
                this.output = false;
                this.state = false;
                this.inputs = [];
                this.outputs = [];
                if (this.type === 'PSU') {
                    this.key = this.generateKey();
                    psuKeys[this.id] = this.key; // Store the generated key
                }
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
                        this.output = this.input1;
                        break;
                    case 'PSU':
                        this.output = true;
                        break;
                    case 'ENC':
                        this.output = this.encrypt(this.input1);
                        break;
                    case 'DEC':
                        this.output = this.decrypt(this.input1);
                        break;
                    case 'CPU':
                        this.output = this.performCPULogic();
                        break;
                    case 'Clock':
                        this.toggleClock();
                        break;
                    case 'IO':
                        this.output = this.input1;
                        break;
                }
                this.state = this.output;
            }

            encrypt(input) {
                const psuId = this.getNearestPSU();
                if (psuId && psuKeys[psuId]) {
                    return input ? this.applyXOR(input, psuKeys[psuId]) : false;
                }
                return false;
            }

            decrypt(input) {
                return this.encrypt(input); // XOR encryption/decryption are symmetric
            }

            applyXOR(input, key) {
                return Boolean(input ^ key);
            }

            performCPULogic() {
                return this.input1 === this.input2;
            }

            toggleClock() {
                clearInterval(clockInterval);
                clockInterval = setInterval(() => {
                    this.input1 = !this.input1;
                    this.updateOutput();
                    renderCircuit();
                }, 500);
            }

            generateKey() {
                return Math.floor(Math.random() * 2); // Generates a 0 or 1 key (Boolean)
            }

            getNearestPSU() {
                return Object.keys(psuKeys)[0]; // For simplicity, return the first PSU ID
            }

            draw() {
                ctx.fillStyle = this.state ? '#e74c3c' : '#3498db';
                ctx.fillRect(this.x, this.y, gridSize, gridSize);
                ctx.fillStyle = '#fff';
                ctx.font = '12px Arial';
                ctx.fillText(this.type, this.x + 5, this.y + 20);
                ctx.fillText(`ID: ${this.id}`, this.x + 5, this.y + 40);
                ctx.fillText(this.output ? 'ON' : 'OFF', this.x + 5, this.y + 60);

                if (this.type === 'PSU') {
                    ctx.fillText(`Key: ${this.key}`, this.x + 5, this.y + 80);
                }
            }

            toggleInput() {
                if (this.type === 'INPUT' || this.type === 'IO') {
                    this.input1 = !this.input1;
                    this.updateOutput();
                }
            }

            isClicked(x, y) {
                return x >= this.x && x <= this.x + gridSize && y >= this.y && y <= this.y + gridSize;
            }

            updatePosition(newX, newY) {
                this.x = newX;
                this.y = newY;
            }
        }

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
                    ctx.strokeStyle = fromGate.output ? '#27ae60' : '#7f8c8d';
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
                        if (toGate.input1 === false || toGate.type === 'NOT' || toGate.type === 'BUFFER' || toGate.type === 'INPUT') {
                            toGate.input1 = this.active;
                        } else if (toGate.input2 === false) {
                            toGate.input2 = this.active;
                        }
                        toGate.updateOutput();
                    }
                }
            }
        }

        function drawGrid() {
            ctx.strokeStyle = '#ddd';
            ctx.lineWidth = 1;
            for (let x = 0; x < canvas.width; x += gridSize) {
                for (let y = 0; y < canvas.height; y += gridSize) {
                    ctx.strokeRect(x, y, gridSize, gridSize);
                }
            }
        }

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

        function toggleGateType(type) {
            selectedGateType = selectedGateType === type ? null : type;
        }

        function renderCircuit() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            drawGrid();
            wires.forEach(wire => wire.draw());
            gates.forEach(gate => gate.draw());
        }

        canvas.addEventListener('click', function (event) {
            const clickX = Math.floor(event.offsetX / gridSize) * gridSize;
            const clickY = Math.floor(event.offsetY / gridSize) * gridSize;

            const clickedGate = gates.find(gate => gate.isClicked(clickX, clickY));
            if (clickedGate) {
                clickedGate.toggleInput();
                updateCircuit();
            } else if (selectedGateType) {
                gates.push(new Gate(clickX, clickY, selectedGateType));
                populateDropdowns();
                renderCircuit();
            }
        });

        function createWire() {
            const startGateId = parseInt(document.getElementById('startGate').value);
            const endGateId = parseInt(document.getElementById('endGate').value);

            if (startGateId && endGateId && startGateId !== endGateId) {
                wires.push(new Wire(startGateId, endGateId));
                updateCircuit();
            } else {
                alert('Invalid wire connection. Please select different start and end gates.');
            }
        }

        function resetCircuit() {
            gates.length = 0;
            wires.length = 0;
            gateIdCounter = 1;
            renderCircuit();
            populateDropdowns();
        }

        function updateCircuit() {
            gates.forEach(gate => gate.updateOutput());
            wires.forEach(wire => wire.updateState());
            renderCircuit();
        }

        function saveCircuit() {
            const circuitData = { gates, wires };
            const json = JSON.stringify(circuitData, null, 2);
            const blob = new Blob([json], { type: 'application/json' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'circuit.json';
            link.click();
        }

        function loadCircuit(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            reader.onload = function (e) {
                const circuitData = JSON.parse(e.target.result);
                gates.length = 0;
                wires.length = 0;
                circuitData.gates.forEach(gate => {
                    gates.push(new Gate(gate.x, gate.y, gate.type, gate.id));
                });
                circuitData.wires.forEach(wire => {
                    wires.push(new Wire(wire.from, wire.to));
                });
                gateIdCounter = Math.max(...gates.map(g => g.id)) + 1;
                updateCircuit();
                populateDropdowns();
            };
            reader.readAsText(file);
        }

        canvas.addEventListener('mousedown', function (event) {
            const clickX = Math.floor(event.offsetX / gridSize) * gridSize;
            const clickY = Math.floor(event.offsetY / gridSize) * gridSize;
            const clickedGate = gates.find(gate => gate.isClicked(clickX, clickY));
            if (clickedGate) {
                draggedGate = clickedGate;
            }
        });

        canvas.addEventListener('mouseup', function () {
            if (draggedGate) {
                draggedGate = null;
            }
        });

        canvas.addEventListener('mousemove', function (event) {
            if (draggedGate) {
                const newX = Math.floor(event.offsetX / gridSize) * gridSize;
                const newY = Math.floor(event.offsetY / gridSize) * gridSize;
                draggedGate.updatePosition(newX, newY);
                renderCircuit();
            }
        });

        drawGrid();
        populateDropdowns();
        renderCircuit();
    </script>
</body>
</html>
