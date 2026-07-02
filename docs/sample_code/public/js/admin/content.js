// 削除時の警告表示
function deleteConfirm(record){
  if(confirm(record+"を削除してもいいですか？")){
    return true;
  }
  return false;
}

// データ更新時の警告表示
function updateConfirm(record) {
  if (confirm(record + "を変更してもいいですか？")) {
    return true;
  } 
    return false;
  }
  

// 警告表示
function alertUser(message){
  if(confirm(message)){
    return true;
  }
  return false;
}

