require('dotenv').config();
const express = require("express");
const http = require("http");
const socketIo = require("socket.io");
const cors = require("cors");
const multer = require("multer");
const mysql = require("mysql2");
const fs = require("fs");
const path = require("path");

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: process.env.FRONTEND_URL || "*",
    methods: ["GET", "POST"]
  }
});

app.use(cors());
app.use(express.json());

const logFile = path.join(__dirname, "server.log");
const log = (message) => {
  const timestamp = new Date().toISOString();
  const logMessage = `[${timestamp}] ${message}\n`;
  console.log(logMessage.trim());
  fs.appendFileSync(logFile, logMessage);
};

const dbConfig = {
  host: process.env.DB_HOST || "localhost",
  user: process.env.DB_USER || "root",
  password: process.env.DB_PASSWORD || "",
  database: process.env.DB_NAME || "fugen_chat_app",
};

let db;

function connectDatabase() {
  db = mysql.createConnection(dbConfig);

  db.connect(err => {
    if (err) {
      log(`Database connection failed: ${err}`);
      setTimeout(connectDatabase, 5000);
    } else {
      log("Connected to MySQL Database");
    }
  });

  db.on("error", (err) => {
    log(`Database error: ${err}`);
    if (err.code === "PROTOCOL_CONNECTION_LOST") {
      connectDatabase();
    } else {
      throw err;
    }
  });
}

connectDatabase();



const users = {};
io.on('connection', (socket) => {
  socket.on('join_room', (userId) => {
    users[userId] = socket.id;
    log(`User ${userId} joined with socket ID: ${socket.id}`);
  });

  socket.on("send_message", (data) => {
    log(`Received message: ${JSON.stringify(data)}`);
    const { sender_id, receiver_id, message, message_id } = data;

    const messageId = message_id;

    console.log("message id");   console.log(messageId);
    
    if (!sender_id || !receiver_id) {
      log(`Error: Missing required fields - ${JSON.stringify(data)}`);
      return;
    }

    const senderNameSql = "SELECT name FROM users WHERE id = ?";
    db.query(senderNameSql, [sender_id], (nameErr, nameResult) => {
      if (nameErr) {
        log(`Error fetching sender name: ${nameErr}`);
        return;
      }

      const senderName = nameResult[0]?.name || 'Unknown Sender';

      const notificationSql = "INSERT INTO notifications (user_id, sender_id, message_id, is_read) VALUES (?, ?, ?, ?)";
      db.query(notificationSql, [receiver_id, sender_id, messageId, false], (notifErr, notifResult) => {
        if (notifErr) {
          log(`Error creating notification: ${notifErr}`);
          return;
        }
        log(`Notification created for user ${receiver_id}`);

        if (users[receiver_id]) {
          log(`Sending message to user ${receiver_id} (Socket: ${users[receiver_id]})`);
          io.to(users[receiver_id]).emit("receive_message", {
            ...data,
            is_read: false,
            message_id: messageId 
          });

          io.to(users[receiver_id]).emit("new_notification", {
            sender_id: sender_id,
            sender_name: senderName,
            message: message,
            created_at: new Date(),
            message_id: messageId || "no message"  
        });
        
        } else {
          log(`User ${receiver_id} is not online`);
        }
      });
    });
  });

  socket.on("mark_messages_read", (data) => {
    const { sender_id, receiver_id } = data;

    const sql = `
      UPDATE messages 
      SET is_read = true 
      WHERE sender_id = ? AND receiver_id = ? AND is_read = false
    `;

    db.query(sql, [sender_id, receiver_id], (err, result) => {
      if (err) {
        log(`Error marking messages as read: ${err}`);
        return;
      }

      log(`Marked ${result.affectedRows} messages as read`);

      if (users[sender_id]) {
        io.to(users[sender_id]).emit("messages_read", {
          sender_id,
          receiver_id,
          read_count: result.affectedRows
        });
      }
    });
  });
  socket.on("mark_notifications_read", (data) => {
    const { user_id, sender_id } = data;

    const sql = `
      UPDATE notifications 
      SET is_read = true 
      WHERE user_id = ? AND sender_id = ? AND is_read = false
    `;

    db.query(sql, [user_id, sender_id], (err, result) => {
      if (err) {
        log(`Error marking notifications as read: ${err}`);
        return;
      }

      log(`Marked ${result.affectedRows} notifications as read`);

      if (users[user_id]) {
        io.to(users[user_id]).emit("notifications_marked_read", {
          sender_id: sender_id,
          read_count: result.affectedRows
        });
      }
    });
  });
});

app.get("/api/notifications", (req, res) => {
  const userId = req.query.user_id;

  if (!userId) {
    return res.status(400).json({ error: "User ID is required" });
  }

  const sql = `
    SELECT n.*, u.name as sender_name 
    FROM notifications n
    JOIN users u ON n.sender_id = u.id
    WHERE n.user_id = ? AND n.is_read = false
    ORDER BY n.created_at DESC
  `;

  db.query(sql, [userId], (err, results) => {
    if (err) {
      log(`Error fetching notifications: ${err}`);
      return res.status(500).json({ error: err.message });
    }
    res.json(results);
  });
});


// io.on('connection', (socket) => {
//   socket.on('join_room', (userId) => {
//     users[userId] = socket.id;
//     log(`User ${userId} joined with socket ID: ${socket.id}`);
//   });

//   socket.on("send_message", (data) => {
//     log(`Received message: ${JSON.stringify(data)}`);
//     const { sender_id, receiver_id, message } = data;

//     if (!sender_id || !receiver_id || !message) {
//       log(`Error: Missing required fields - ${JSON.stringify(data)}`);
//       return;
//     }

//     // Updated SQL to include is_read column
//     const sql = "INSERT INTO messages (sender_id, receiver_id, message, is_read) VALUES (?, ?, ?, ?)";
//     db.query(sql, [sender_id, receiver_id, message, false], (err, result) => {
//       if (err) {
//         log(`Error saving message: ${err}`);
//         return;
//       }
//       log(`Message saved to DB: ID ${result.insertId}`);

//       if (users[receiver_id]) {
//         log(`Sending message to user ${receiver_id} (Socket: ${users[receiver_id]})`);
//         // Emit to the specific socket of the receiver
//         io.to(users[receiver_id]).emit("receive_message", {
//           ...data,
//           is_read: false,
//           message_id: result.insertId
//         });
//       } else {
//         log(`User ${receiver_id} is not online`);
//       }
//     });
//   });

//   // New event handler for marking messages as read
//   socket.on("mark_messages_read", (data) => {
//     const { sender_id, receiver_id } = data;

//     const sql = `
//       UPDATE messages 
//       SET is_read = true 
//       WHERE sender_id = ? AND receiver_id = ? AND is_read = false
//     `;

//     db.query(sql, [sender_id, receiver_id], (err, result) => {
//       if (err) {
//         log(`Error marking messages as read: ${err}`);
//         return;
//       }

//       log(`Marked ${result.affectedRows} messages as read`);

//       // Notify the sender that messages have been read
//       if (users[sender_id]) {
//         io.to(users[sender_id]).emit("messages_read", {
//           sender_id,
//           receiver_id,
//           read_count: result.affectedRows
//         });
//       }
//     });
//   });

//   socket.on('disconnect', () => {
//     for (let userId in users) {
//       if (users[userId] === socket.id) {
//         delete users[userId];
//         log(`User ${userId} disconnected`);
//         break;
//       }
//     }
//   });
// });

// // Updated messages endpoint to include is_read status
// app.post("/messages", (req, res) => {
//   const { sender_id, receiver_id } = req.body;
//   log(`Fetching messages between ${sender_id} and ${receiver_id}`);

//   if (!sender_id || !receiver_id) {
//     return res.status(400).json({ error: "Both sender_id and receiver_id are required" });
//   }

//   const sql = `
//     SELECT * FROM messages 
//     WHERE (sender_id = ? AND receiver_id = ?) 
//     OR (sender_id = ? AND receiver_id = ?) 
//     ORDER BY created_at
//   `;

//   db.query(sql, [sender_id, receiver_id, receiver_id, sender_id], (err, results) => {
//     if (err) {
//       log(`Error fetching messages: ${err}`);
//       return res.status(500).json({ error: err.message });
//     }
//     log(`Fetched ${results.length} messages`);
//     res.json(results);
//   });
// });

// // New endpoint to mark messages as read
// app.post("/mark-messages-read", (req, res) => {
//   const { sender_id, receiver_id } = req.body;

//   const sql = `
//     UPDATE messages 
//     SET is_read = true 
//     WHERE sender_id = ? AND receiver_id = ? AND is_read = false
//   `;

//   db.query(sql, [sender_id, receiver_id], (err, result) => {
//     if (err) {
//       log(`Error marking messages as read: ${err}`);
//       return res.status(500).json({ error: err.message });
//     }

//     log(`Marked ${result.affectedRows} messages as read`);
//     res.json({ 
//       message: 'Messages marked as read', 
//       updated_count: result.affectedRows 
//     });
//   });
// });

server.listen(3000, () => {
  log("Socket.IO server running on port 3000");
});