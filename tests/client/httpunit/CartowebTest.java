import junit.framework.Test;
import junit.framework.TestCase;
import junit.framework.TestSuite;

import com.meterware.httpunit.Button;
import com.meterware.httpunit.GetMethodWebRequest;
import com.meterware.httpunit.SubmitButton;
import com.meterware.httpunit.WebConversation;
import com.meterware.httpunit.WebForm;
import com.meterware.httpunit.HTMLElement;
import com.meterware.httpunit.WebRequest;
import com.meterware.httpunit.WebResponse;

/**
 * Test class for Cartoweb HttpUnit.
 * 
 * @author sypasche
 */
public class CartowebTest extends TestCase {

    static String URL;
    
    public CartowebTest(String name) {
        super(name);
    }

    public static void main(String args[]) {        
        if (args.length != 1) {
            System.out.println("Usage: CartowebTest CARTOCLIENT_URL\n");
            System.exit(0);
        }
        CartowebTest.URL = args[0];

        junit.textui.TestRunner.run(suite());
    }
    
    public static Test suite() {
        if (URL == null)
            throw new RuntimeException("Please invoke this test via main(), with URL argument");
        
        return new TestSuite(CartowebTest.class);
    }

    private void assertContainsMainmap(WebResponse response) throws Exception {
        
        WebForm forms[] = response.getForms();
        assertEquals(1, forms.length);

        HTMLElement mainmap = response.getElementWithID("mapImageDiv");
        assertNotNull("No mainmap image on cartoclient page", mainmap);
    }
    
    public void testMainmapPresent() throws Exception {
        
        WebConversation     conversation = new WebConversation();
        WebRequest request = new GetMethodWebRequest(URL);
        WebResponse response = conversation.getResponse(request);
        
        assertContainsMainmap(response);
    }
    
    public void testKeymap() throws Exception {
                
        WebConversation     conversation = new WebConversation();
        WebResponse response = conversation.getResponse(URL);

        assertContainsMainmap(response);

        // click on the center of the keymap
        SubmitButton keymapButton = response.getFormWithName("carto_form").getSubmitButton("keymap");
        keymapButton.click(50, 50);
        assertContainsMainmap(conversation.getCurrentPage());
    }
}
