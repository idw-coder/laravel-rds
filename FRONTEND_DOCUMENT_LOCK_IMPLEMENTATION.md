# フロントエンド実装指示書：共有ドキュメント編集ロック機能

## 概要

共有ドキュメントに編集ロック機能を追加します。1人のユーザーのみが編集可能にし、他のユーザーには「編集中」の表示を行います。**認証不要**で、セッションIDでユーザーを識別します。

---

## 1. APIエンドポイント仕様

### 1.1 ロック取得
**エンドポイント**: `POST /api/documents/{roomId}/lock`  
**認証**: 不要  
**リクエスト**: なし（セッションIDは自動で送信される）

**レスポンス（成功時）**:
```json
{
  "success": true,
  "locked_at": "2024-12-19T12:00:00Z"
}
```

**レスポンス（失敗時 - 既にロックされている）**:
```json
{
  "success": false,
  "error": "already_locked",
  "message": "他のユーザーが編集中です",
  "locked_at": "2024-12-19T11:58:00Z"
}
```
**HTTPステータス**: `409 Conflict`

### 1.2 ロック解放
**エンドポイント**: `DELETE /api/documents/{roomId}/lock`  
**認証**: 不要  
**リクエスト**: なし

**レスポンス（成功時）**:
```json
{
  "success": true,
  "message": "ロックを解放しました。"
}
```

**レスポンス（失敗時 - ロックしていない）**:
```json
{
  "success": false,
  "error": "not_locked_by_user",
  "message": "あなたはロックを保持していません"
}
```
**HTTPステータス**: `403 Forbidden`

**レスポンス（ロックが存在しない場合）**:
```json
{
  "success": true,
  "message": "ロックは既に解放されています。"
}
```
**HTTPステータス**: `200 OK`

### 1.3 ロック状態確認
**エンドポイント**: `GET /api/documents/{roomId}/lock`  
**認証**: 不要

**レスポンス（ロックされている場合）**:
```json
{
  "is_locked": true,
  "locked_at": "2024-12-19T12:00:00Z"
}
```

**レスポンス（ロックされていない場合）**:
```json
{
  "is_locked": false
}
```

---

## 2. WebSocketイベント仕様

### 2.1 ロック取得時のイベント
**チャンネル**: `document.{roomId}`  
**イベント名**: `document.locked`  
**イベントデータ**:
```json
{
  "room_id": "room123",
  "session_id": "abc123...",
  "locked_at": "2024-12-19T12:00:00Z"
}
```

### 2.2 ロック解放時のイベント
**チャンネル**: `document.{roomId}`  
**イベント名**: `document.unlocked`  
**イベントデータ**:
```json
{
  "room_id": "room123",
  "session_id": "abc123..."
}
```

---

## 3. 実装要件

### 3.1 編集開始時の処理

1. **ロック取得を試みる**
   ```typescript
   // エディタを開いた時、または編集を開始しようとした時
   const response = await fetch(`/api/documents/${roomId}/lock`, {
     method: 'POST',
     credentials: 'include' // セッションCookieを送信
   });
   
   if (response.status === 409) {
     // 他のユーザーが編集中
     // UIに「他のユーザーが編集中です」と表示
     // エディタを読み取り専用にする
     return;
   }
   
   if (response.ok) {
     // ロック取得成功
     // エディタを編集可能にする
   }
   ```

### 3.2 編集終了時の処理

1. **ロック解放**
   ```typescript
   // ページを離れる時、または編集を終了する時
   window.addEventListener('beforeunload', async () => {
     await fetch(`/api/documents/${roomId}/lock`, {
       method: 'DELETE',
       credentials: 'include'
     });
   });
   ```

### 3.3 WebSocketイベントの受信

```typescript
// Laravel Echoの設定（既存の設定を使用）
Echo.channel(`document.${roomId}`)
  .listen('.document.locked', (e: any) => {
    // 他のユーザーがロックを取得した
    // 自分のロックでない場合、エディタを読み取り専用にする
    if (e.session_id !== currentSessionId) {
      // UIに「他のユーザーが編集中です」と表示
      // エディタを読み取り専用にする
    }
  })
  .listen('.document.unlocked', (e: any) => {
    // ロックが解放された
    // UIの「編集中」表示を削除
    // エディタを編集可能にする（必要に応じて）
  });
```

### 3.4 UI表示要件

#### 編集中の表示
- **自分のロックの場合**:
  - エディタ上部に「編集中」バッジを表示（緑色推奨）
  - エディタは編集可能

- **他のユーザーのロックの場合**:
  - エディタ上部に「他のユーザーが編集中です」メッセージを表示（黄色/オレンジ色推奨）
  - エディタを読み取り専用にする
  - 「編集を待つ」ボタンを表示（オプション）

#### ロック状態の確認
- ページ読み込み時、または定期的に（例：10秒ごと）`GET /api/documents/{roomId}/lock`を呼び出してロック状態を確認
- ロック状態が変化した場合、UIを更新

---

## 4. 実装フロー

### 4.1 ページ読み込み時
```
1. ドキュメントを取得（既存のAPI）
2. ロック状態を確認（GET /api/documents/{roomId}/lock）
3. ロックされている場合:
   - エディタを読み取り専用にする
   - 「他のユーザーが編集中です」と表示
4. WebSocketチャンネルを購読
```

### 4.2 編集開始時
```
1. ロック取得を試みる（POST /api/documents/{roomId}/lock）
2. 成功した場合:
   - エディタを編集可能にする
   - 「編集中」バッジを表示
3. 失敗した場合（409 Conflict）:
   - 「他のユーザーが編集中です」と表示
   - エディタを読み取り専用のままにする
```

### 4.3 編集終了時
```
1. ロックを解放（DELETE /api/documents/{roomId}/lock）
2. 「編集中」バッジを削除
```

### 4.4 WebSocketイベント受信時
```
1. document.locked イベントを受信:
   - 自分のロックでない場合:
     - エディタを読み取り専用にする
     - 「他のユーザーが編集中です」と表示

2. document.unlocked イベントを受信:
   - 「編集中」表示を削除
   - エディタを編集可能にする（必要に応じて）
```

---

## 5. エラーハンドリング

### 5.1 ネットワークエラー
- ロック取得/解放が失敗した場合、リトライロジックを実装
- 3回連続で失敗した場合、エラーメッセージを表示

### 5.2 ロック競合
- `409 Conflict`が返された場合、定期的にロック状態を確認
- ロックが解放されたら、自動的にロック取得を再試行（オプション）

### 5.3 WebSocket切断
- WebSocketが切断された場合、再接続処理を実装
- 再接続後、ロック状態を再確認

---

## 6. ビジネスロジックの説明

### 6.1 ロックの保持
- **ロック取得時**: ロックを作成
- **ロック解放**: DELETE /lockで明示的にロックを削除
- **ロックは無期限**: フロントからDELETE /lockが来るまでロックを保持

### 6.2 セッションIDによる識別
- 認証不要で、セッションIDでユーザーを識別
- 同じブラウザセッションでは同じセッションIDが使用される
- `credentials: 'include'`を指定してCookieを送信する必要がある

### 6.3 ロックの競合
- 1つのドキュメントに1つのロックのみ
- 他のユーザーがロックしている場合、`409 Conflict`が返される
- 自分のロックの場合、既存のロックを更新（削除して再作成）

---

## 7. 実装例（TypeScript/Vue）

### 7.1 ロック管理のComposable/Service

```typescript
// composables/useDocumentLock.ts または services/documentLock.ts

import { ref, onUnmounted } from 'vue';

export function useDocumentLock(roomId: string) {
  const isLocked = ref(false);
  const isMyLock = ref(false);

  // ロック取得
  const acquireLock = async (): Promise<boolean> => {
    try {
      const response = await fetch(`/api/documents/${roomId}/lock`, {
        method: 'POST',
        credentials: 'include'
      });

      if (response.status === 409) {
        // 他のユーザーが編集中
        isLocked.value = true;
        isMyLock.value = false;
        return false;
      }

      if (response.ok) {
        isLocked.value = true;
        isMyLock.value = true;
        return true;
      }

      return false;
    } catch (error) {
      console.error('ロック取得エラー:', error);
      return false;
    }
  };

  // ロック解放
  const releaseLock = async (): Promise<void> => {
    try {
      await fetch(`/api/documents/${roomId}/lock`, {
        method: 'DELETE',
        credentials: 'include'
      });
    } catch (error) {
      console.error('ロック解放エラー:', error);
    } finally {
      isLocked.value = false;
      isMyLock.value = false;
    }
  };

  // ロック状態確認
  const checkLockStatus = async (): Promise<void> => {
    try {
      const response = await fetch(`/api/documents/${roomId}/lock`, {
        credentials: 'include'
      });
      const data = await response.json();
      
      isLocked.value = data.is_locked;
      // セッションIDの比較はサーバー側で行うため、フロント側では判断が難しい
      // WebSocketイベントで判断する
    } catch (error) {
      console.error('ロック状態確認エラー:', error);
    }
  };

  // クリーンアップ
  onUnmounted(() => {
    releaseLock();
    window.removeEventListener('beforeunload', releaseLock);
  });

  // ページ離脱時の処理
  window.addEventListener('beforeunload', releaseLock);

  return {
    isLocked,
    isMyLock,
    acquireLock,
    releaseLock,
    checkLockStatus
  };
}
```

### 7.2 WebSocketイベントの購読

```typescript
// composables/useDocumentLockEvents.ts

import Echo from 'laravel-echo';
import { ref } from 'vue';

export function useDocumentLockEvents(roomId: string, onLocked: () => void, onUnlocked: () => void) {
  // Laravel Echoの設定（既存の設定を使用）
  const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
  });

  echo.channel(`document.${roomId}`)
    .listen('.document.locked', (e: any) => {
      // 他のユーザーがロックを取得した
      onLocked();
    })
    .listen('.document.unlocked', (e: any) => {
      // ロックが解放された
      onUnlocked();
    });

  return {
    disconnect: () => {
      echo.leave(`document.${roomId}`);
    }
  };
}
```

### 7.3 Vueコンポーネントでの使用例

```vue
<template>
  <div>
    <!-- ロック状態の表示 -->
    <div v-if="isLocked && !isMyLock" class="alert alert-warning">
      他のユーザーが編集中です
    </div>
    <div v-if="isMyLock" class="badge badge-success">
      編集中
    </div>

    <!-- エディタ -->
    <textarea
      v-model="content"
      :disabled="isLocked && !isMyLock"
      @focus="handleEditorFocus"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useDocumentLock } from '@/composables/useDocumentLock';
import { useDocumentLockEvents } from '@/composables/useDocumentLockEvents';

const props = defineProps<{
  roomId: string;
}>();

const content = ref('');
const { isLocked, isMyLock, acquireLock, releaseLock, checkLockStatus } = useDocumentLock(props.roomId);

// WebSocketイベントの購読
const { disconnect } = useDocumentLockEvents(
  props.roomId,
  () => {
    // 他のユーザーがロックを取得した
    isMyLock.value = false;
  },
  () => {
    // ロックが解放された
    isLocked.value = false;
  }
);

// エディタフォーカス時の処理
const handleEditorFocus = async () => {
  if (!isMyLock.value) {
    const success = await acquireLock();
    if (!success) {
      alert('他のユーザーが編集中です');
    }
  }
};

// 初期化
onMounted(async () => {
  await checkLockStatus();
});

// クリーンアップ
onUnmounted(() => {
  disconnect();
});
</script>
```

---

## 8. 注意事項

1. **セッションCookie**: `credentials: 'include'`を必ず指定
2. **ロック解放**: ページ離脱時や編集終了時に必ずDELETE /lockを呼び出す
3. **エラーハンドリング**: ネットワークエラー時のリトライロジックを実装
4. **UI/UX**: ロック状態を明確に表示し、ユーザーに分かりやすくする
5. **パフォーマンス**: ロック状態確認の頻度を適切に設定（10秒ごと推奨）

---

## 9. テストシナリオ

1. **正常系**: ロック取得 → 編集 → ロック解放
2. **競合**: 2人のユーザーが同時にロック取得を試みる
3. **ページ離脱**: ブラウザを閉じる前にロック解放が実行されることを確認
4. **WebSocket**: 他のユーザーがロック取得/解放した時の動作確認

---

この指示書をフロントエンドAIに共有してください。
