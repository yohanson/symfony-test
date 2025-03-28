
export function toISODateString(timestamp) {
    return new Date(timestamp - (new Date()).getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

