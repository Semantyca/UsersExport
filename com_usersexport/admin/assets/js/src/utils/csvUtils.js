export const convertToCSV = (data, includeHeader = true, headers = []) => {
    if (data.length === 0) return '';

    const filteredHeaders = headers.map(header => header.split('.').pop());

    const array = includeHeader ? [filteredHeaders].concat(data) : data;

    return array.map((row, index) => {
        if (index === 0 && includeHeader) {
            return filteredHeaders.map(key => `"${key}"`).join(',');
        }
        return filteredHeaders.map(key => `"${row[key] !== undefined ? row[key] : ''}"`).join(',');
    }).join('\n');
};

export const exportCSV = (data, filename = 'users_export.csv', includeHeader = true, headers = []) => {
    if (data.length === 0) {
        alert('No data available to export.');
        return;
    }
    const csv = convertToCSV(data, includeHeader, headers);
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
