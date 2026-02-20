const EXCEPTION_TYPE_BRACKETS = "brackets";
const EXCEPTION_TYPE_AND_OR = "and_or";
const EXCEPTION_TYPE_ARGUMENT_NUMBER = "argument_number";

function buildParseException(msg, type){
    return {
        message: msg,
        type: type,
    };
}

function parseConditions(conditions){

    // replace all double spaces
    conditions = conditions.replace(/ {1,}/g," ");

    // find nested conditions
    const nestedConditions = [];
    let i = 0;
    do{
        const pos = conditions.indexOf("(");
        if(pos < 0) break;
        const posEnd = conditions.indexOf(")");
        if(posEnd < 0) throw buildParseException("Syntax error: missing closing bracket.", EXCEPTION_TYPE_BRACKETS);

        const brackets = conditions.substring(pos+1, posEnd);
        nestedConditions.push(brackets); 
        conditions = conditions.replace(`(${brackets})`, `$${i}$`);
        i++;
    }while(i < 100);

    // break down to main condition parts and filter empty items
    const mainConditions = conditions.split(" ").filter((item)=> item);

    if(mainConditions.length % 2 !== 1){
        throw buildParseException(
            `Syntax error: expression seems to have a weired argument number of ${mainConditions.length}. This should be an odd number.`,
            EXCEPTION_TYPE_ARGUMENT_NUMBER
        );
    }
        

    // validate and map to result
    return mainConditions.map((item, index)=>{
        const isOdd = index % 2;
        if(isOdd && item.toUpperCase() !== "AND" && item.toUpperCase() !== "OR"){
            throw buildParseException(`Unknown item: ${item} . Should be AND or OR`, EXCEPTION_TYPE_AND_OR);
        } else if(m = /^\$(\d+)\$$/gm.exec(item)){
            const number = m[1];
            const nested = nestedConditions[number];
            return parseConditions(nested);
        }
        return item;
    });
}

function isValidConditions(conditions, allowed_topic_ids){
    let index;
    for( index in conditions){
        if(!conditions.hasOwnProperty(index)) continue;
        const cond = conditions[index];

        if(index % 2) continue;

        if(typeof cond === typeof []){
            if(!isValidConditions(cond, allowed_topic_ids)) return false;
        } else if(typeof cond === typeof ""){
            if(allowed_topic_ids.indexOf(cond) < 0) return false;
        } else {
            return false;
        }
    }
    return true;
}

function isInConditionLimitations(conditions){
    const doesCount = (item)=> item.toUpperCase() === "AND" || item.toUpperCase() === "OR";
    // max of 4 conditionals
    const reduced = conditions.reduce((value, item)=>{
        if(typeof item === typeof []) return value + item.reduce((value, item)=> (doesCount(item))?value+1:value,0);
        if(doesCount(item)) return value+1;
        return value;
    }, 0);

    return reduced <= 4;
}

try{
    const condition = "android and (topic1 OR topic2) AND topic1 AND topic3 AND topic1";
    const result = parseConditions(condition);
    const isValid = isValidConditions(result, ['topic1', 'topic2', 'ios', 'android']);
    const inLimitations = isInConditionLimitations(result);
    inLimitations;
    isValid;
} catch(e){
    e;
}

