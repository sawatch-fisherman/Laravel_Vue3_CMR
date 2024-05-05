// 改行対応
const nl2br = (str) => {
    var res = str.replace(/\r\n/g, "<br>");
    res = res.replace(/(\n|\r)/g, "<br>");
    return res;
}

const getToday = () => {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = ("0" + (today.getMonth() + 1)).slice(-2);
    const dd = ("0" + today.getDate()).slice(-2);
    return yyyy + '-' + mm + '-' + dd;
}

// 外部から呼び出し可能にする
export { nl2br, getToday }
