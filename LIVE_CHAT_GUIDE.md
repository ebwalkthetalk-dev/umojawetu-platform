# 💬 Live Chat Support System

**Status:** ✅ Fully Implemented & Ready to Use  
**Date:** April 13, 2026  
**Platform:** Umojawetu Platform

---

## Overview

Complete live chat support system with:
- 🟢 Real-time messaging for visitors
- 👨‍💼 Admin chat management interface
- 💾 Message persistence and history
- 📊 Visitor tracking and status
- 🎯 Conversation management

---

## System Architecture

### Database Schema

**ChatConversations Table:**
- `Id` - Primary key
- `VisitorId` - Unique visitor identifier
- `VisitorName` - Visitor's name
- `VisitorEmail` - Visitor's email
- `VisitorPhone` - Visitor's phone (optional)
- `Status` - active/waiting/closed
- `CreatedAt` - Conversation start time
- `UpdatedAt` - Last activity time
- `ClosedAt` - When conversation was closed
- `Notes` - Admin closing notes
- `AssignedTo` - Assigned admin username

**ChatMessages Table:**
- `Id` - Primary key
- `ConversationId` - FK to conversations
- `SenderType` - visitor/admin
- `SenderName` - Name of sender
- `SenderEmail` - Email of sender
- `Message` - Message content
- `IsRead` - Read status (0/1)
- `CreatedAt` - Message timestamp

**ChatVisitors Table:**
- `Id` - Primary key
- `VisitorId` - Unique identifier
- `IP` - Visitor's IP address
- `UserAgent` - Browser info
- `CurrentPage` - Current page URL
- `IsOnline` - Online status (0/1)
- `LastSeen` - Last activity time
- `Location` - Visitor location (if available)
- `Device` - mobile/tablet/desktop

---

## Components

### 1. Admin Chat Interface
**Location:** `/admin/chat.php`

**Features:**
- 📋 List of all conversations
  - Filter by: All, Waiting, Active, Closed
  - Show unread count
  - Display last activity time
- 💬 Chat window for selected conversation
  - Full message history
  - Participant info (name, email, phone)
  - Status badges
- ✅ Send messages as admin
- 🔴 Close conversations with notes
- 📊 Real-time updates (auto-refresh every 3 seconds)
- 🟢 Online visitor count

**Access:** Admin only (admin/chat.php)

### 2. Public Chat Widget
**Location:** `/public/chat-widget.php`

**Features:**
- 💬 Floating chat bubble (red, fixed position)
- 📱 Responsive chat window (380px wide)
- ✉️ Send/receive messages
- 💾 Message history stored locally
- 🔄 Real-time message updates (polling every 3 seconds)
- 🎨 Professional styling with animations
- 📱 Works on all devices (mobile, tablet, desktop)

**Usage:** Add to any page:
```html
<script src="/public/chat-widget.php"></script>
```

### 3. API Endpoints

#### POST `/api/chat/send-message.php`
Send a chat message

**Parameters:**
```json
{
    "conversation_id": 1,
    "message": "Hello, how can I help?",
    "sender_type": "admin|visitor",
    "sender_name": "John Doe",
    "sender_email": "john@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "id": 42,
    "message": "Message sent successfully"
}
```

#### GET `/api/chat/get-messages.php?id=1`
Get conversation messages

**Parameters:**
- `id` - Conversation ID (required)
- `limit` - Messages per page (default: 50)
- `offset` - Pagination offset (default: 0)

**Response:**
```json
{
    "success": true,
    "count": 5,
    "messages": [
        {
            "Id": 1,
            "ConversationId": 1,
            "SenderType": "visitor",
            "SenderName": "John",
            "Message": "Hello!",
            "IsRead": 1,
            "CreatedAt": "2026-04-13 10:30:00"
        }
    ]
}
```

#### GET `/api/chat/get-conversations.php?status=all`
Get all conversations (admin only)

**Parameters:**
- `status` - all|active|waiting|closed (default: all)

**Response:**
```json
{
    "success": true,
    "count": 5,
    "unread": 2,
    "conversations": [...]
}
```

#### POST `/api/chat/create-conversation.php`
Create a new conversation

**Parameters:**
```json
{
    "visitor_id": "abc123def456",
    "visitor_name": "John Doe",
    "visitor_email": "john@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "conversation_id": 1
}
```

#### POST `/api/chat/update-visitor.php`
Update visitor status

**Parameters:**
```json
{
    "visitor_id": "abc123def456",
    "is_online": true,
    "current_page": "/events"
}
```

---

## Helper Functions

All functions in `/config/chat.php`:

### Database Management
- `initializeChatTables()` - Create tables
- `getOrCreateConversation($visitorId, $name, $email)` - Create/get conversation
- `getConversationDetails($conversationId)` - Get conversation info

### Messages
- `sendChatMessage($conversationId, $message, $type, $name, $email)` - Send message
- `getConversationMessages($conversationId, $limit, $offset)` - Get messages
- `markMessagesAsRead($conversationId, $type)` - Mark as read

### Conversations
- `getActiveConversations($status)` - Get all conversations
- `updateConversationStatus($conversationId, $status, $notes)` - Change status
- `getUnreadChatCount()` - Count unread messages

### Visitors
- `updateVisitorStatus($visitorId, $isOnline, $page)` - Update status
- `getOnlineVisitorsCount()` - Count online visitors

---

## How to Use

### For Visitors

1. **Browse any public page** (e.g., /public/events.php)
2. **Click the 💬 bubble** in bottom-right corner
3. **Type your message** and click Send
4. **Wait for admin response** (real-time updates)
5. **Close window** by clicking ✕

### For Admins

1. **Go to Admin Panel** → **💬 Live Chat**
2. **View all conversations** in left sidebar
3. **Click a conversation** to view details
4. **Read messages** from visitor
5. **Type reply** in message box and click Send
6. **Close chat** when done (optional notes)

### Embedding on Pages

Add this single line to any page:
```html
<script src="/public/chat-widget.php"></script>
```

The widget will automatically:
- Generate unique visitor ID from IP + User Agent
- Create localStorage entries for conversation tracking
- Display chat bubble
- Handle real-time updates

---

## Key Features

✅ **Real-Time Messaging**
- Polling-based updates every 3 seconds
- No WebSocket setup required
- Works on all browsers

✅ **Conversation Management**
- Create, view, update, close conversations
- Status tracking (waiting, active, closed)
- Unread message counts
- Last activity timestamps

✅ **Visitor Tracking**
- Unique visitor IDs (IP + User Agent hash)
- Online/offline status
- Current page tracking
- Device detection

✅ **Admin Features**
- Multiple conversation management
- Filter conversations by status
- Send/receive messages
- Close with notes
- View conversation history
- Unread message badges

✅ **Security**
- Admin login required for admin endpoints
- CORS headers on all APIs
- Input validation and sanitization
- SQL injection prevention

✅ **User Experience**
- Beautiful UI with responsive design
- Animations and transitions
- Auto-scroll to latest messages
- Message timestamps
- Sender identification (visitor/admin badges)

---

## Configuration

### Enable Chat Widget on Pages

Add to footer or header template (_layout.php):
```php
<script src="<?php echo $site_url; ?>/public/chat-widget.php"></script>
```

### Customize Widget

Edit `/public/chat-widget.php`:
- Line 11-16: Customize color (Umoja red: #d12828)
- Line 21-28: Change default icon (💬 → emoji)
- Line 32-36: Modify greeting message

### Database Location

Tables created automatically in:
`/storage/umoja-events-database.db`

---

## Testing

### Test as Visitor
1. Open `/public/events.php`
2. Click chat bubble
3. Send test message
4. Check admin chat for message

### Test as Admin
1. Go to `/admin/chat.php`
2. Select conversation from list
3. Send reply
4. Check widget for response

### Check Status
- Online visitors: See count in admin chat header
- Unread messages: Badge on 💬 chat icon
- Conversation status: Active, Waiting, or Closed

---

## Database

Auto-initialized tables:
- ✅ ChatConversations
- ✅ ChatMessages
- ✅ ChatVisitors

**Location:** `/storage/umoja-events-database.db`

---

## Admin Navigation

Added to admin sidebar:
- 💬 Live Chat (with unread badge)
- Shows real-time unread count
- Direct link to chat management

---

## Files Added

```
config/
  └── chat.php                    (Helper functions)
admin/
  └── chat.php                    (Admin interface)
public/
  └── chat-widget.php             (Public chat bubble widget)
api/chat/
  ├── send-message.php            (Send message API)
  ├── get-messages.php            (Get messages API)
  ├── get-conversations.php        (Get conversations API)
  ├── create-conversation.php      (Create conversation API)
  └── update-visitor.php           (Update visitor status API)
```

---

## Next Steps (Optional Enhancements)

- [ ] Add email notifications for new messages
- [ ] Implement typing indicators
- [ ] Add attachment support
- [ ] Schedule auto-close for inactive chats
- [ ] Add chat transcript export
- [ ] Implement canned responses/quick replies
- [ ] Add chatbot integration
- [ ] Real-time notifications (WebSocket)
- [ ] Multi-language support
- [ ] Analytics dashboard

---

## Troubleshooting

**Widget not appearing:**
- Check script tag is included
- Check browser console for errors
- Verify page includes chat-widget.php

**Messages not sending:**
- Check admin is logged in (for admin messages)
- Check conversation ID is valid
- Check API endpoints are accessible (/api/chat/)

**Not receiving updates:**
- Check browser allows automatic refresh
- Verify no JavaScript errors
- Check localStorage is enabled

---

## Support

For issues or questions:
1. Check `/storage/logs/` for errors
2. Verify database tables exist in SQLite
3. Check API endpoints return valid JSON

---

**System Status:** ✅ Production Ready

