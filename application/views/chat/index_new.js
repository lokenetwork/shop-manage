/**
 * Created by loken_mac on 1/27/16.
 */


var WSMessage;
var AuthMessage;
var Message;
var wsmessage;
var authmessage;
var message;
var buffer;
var text_buffer;
var content_buffer;
protobuf.load("/static/protobuf/Message.proto", function (err, root) {
	if (err) throw err;
	WSMessage = root.lookup("main.WSMessage");
	AuthMessage = root.lookup("main.AuthMessage");
	Message = root.lookup("main.Message");

	var  token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImp0aSI6IjRmMWcyM2ExMmFhIn0.eyJpc3MiOiJodHRwOlwvXC9leGFtcGxlLmNvbSIsImF1ZCI6Imh0dHA6XC9cL2V4YW1wbGUub3JnIiwianRpIjoiNGYxZzIzYTEyYWEiLCJpYXQiOjE1NTQxMjk3OTQsIm5iZiI6MTU1NDEyOTczNCwiZXhwIjoxNTU0MTMzMzk0LCJjbGllbnRfaWQiOiIxIn0.XjZNIMkbtraRe9-GINoSKNGrjHUu_UFlsIi0pVbGod4";
	token="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImp0aSI6IjRmMWcyM2ExMmFhIn0.eyJpc3MiOiJodHRwOlwvXC9leGFtcGxlLmNvbSIsImF1ZCI6Imh0dHA6XC9cL2V4YW1wbGUub3JnIiwianRpIjoiNGYxZzIzYTEyYWEiLCJpYXQiOjE1NTQyNzQ5MDUsIm5iZiI6MTU1NDI3NDg0NSwiZXhwIjoxNTU0MzYxMzA1LCJjbGllbnRfaWQiOiIxIn0.3x6yAlBK-yxbyiRtN897ihBkWsZBUUOvNWX6T2XqiT0";
	authmessage = AuthMessage.create({token: token});
	content_buffer = AuthMessage.encode(authmessage).finish()
	wsmessage = WSMessage.create({type: "shoper_auth", content: content_buffer});
	buffer = WSMessage.encode(wsmessage).finish();



});

var wsUri = "ws://127.0.0.1:8080/ws";
var output;

function init() {
	output = document.getElementById("output");
	testWebSocket();
}

function testWebSocket() {
	websocket = new WebSocket(wsUri);
	websocket.onopen = function (evt) {
		onOpen(evt)
	};
	websocket.onclose = function (evt) {
		onClose(evt)
	};
	websocket.onmessage = function (evt) {
		console.log(evt);
		onMessage(evt)
	};
	websocket.onerror = function (evt) {
		onError(evt)
	};
}

function mySend() {
	message = Message.create({
		from: "shoper",
		messageType: "text",
		fromUserId: 121,
		toUserId: 1,
		content: "这件衣服"+Math.ceil(Math.random()*100)+"块。"
	});
	content_buffer = Message.encode(message).finish()
	wsmessage = WSMessage.create({type: "message", content: content_buffer});
	text_buffer = WSMessage.encode(wsmessage).finish();

	writeToScreen("SENT: " + text_buffer);
	websocket.send(text_buffer);
}

function onOpen(evt) {
	writeToScreen("CONNECTED");
	doSend(buffer);
}

function onClose(evt) {
	writeToScreen("DISCONNECTED");
}

function onMessage(evt) {
	var reader = new FileReader();
	reader.readAsArrayBuffer(evt.data);
	reader.onload = function (e) {

		var buf = new Uint8Array(reader.result);
		var content = WSMessage.decode(buf).content;
		var message = Message.decode(content);
		console.log(message);
		writeToScreen('<span style="color: blue;">RESPONSE: ' + message.content + '</span>');
	}
}

function onError(evt) {
	writeToScreen('<span style="color: red;">ERROR:</span> ' + evt.data);
}

function doSend() {
	writeToScreen("SENT: " + wsmessage.content);
	websocket.send(buffer);
}

function writeToScreen(message) {
	var pre = document.createElement("p");
	pre.style.wordWrap = "break-word";
	pre.innerHTML = message;
	output.appendChild(pre);
}

window.addEventListener("load", init, false);
