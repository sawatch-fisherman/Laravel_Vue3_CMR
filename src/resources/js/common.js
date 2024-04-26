// 改行対応
const nl2br = (str) => {
    var res = str.replace(/\r\n/g, "<br>");
    res = res.replace(/(\n|\r)/g, "<br>");
    return res;
}

// 外部から呼び出し可能にする
export { nl2br }
